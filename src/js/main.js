import { TransferTransaction, Hbar, AccountId, TokenId } from "@hashgraph/sdk";

// Main thread
(function () {
    "use strict";

    console.log("pay!");

    const USDC_TOKEN_MAP = {
        mainnet: "0.0.456858",
        testnet: "0.0.429274",
    };

    // Helper to update the notice message
    const updateNotice = (element, message) => {
        element.innerText = message;
    };

    setupPayButtons();

    var checkoutButton = document.querySelector("#hashpress-pay-woocommerce .hashpress-pay .pay");

    // Trigger the button click event if it exists
    if (checkoutButton) {
        checkoutButton.click();
    }

    function setupPayButtons() {
        let instances = document.querySelectorAll(".hashpress-pay");

        [...instances].forEach((instance) => {
            const payButton = instance.querySelector(".pay");
            const notice = instance.querySelector(".notice");

            payButton.addEventListener("click", async function () {
                console.log("clicked!");
                updateNotice(notice, "");

                const buttonData = await fetchDataById(payButton.dataset.id);

                await ensureCorrectNetwork(buttonData.network, notice);

                const acceptedCurrency = buttonData.accepts.toUpperCase();

                let result =
                    acceptedCurrency === "HBAR"
                        ? await handleHBARTransaction(payButton, buttonData, notice)
                        : await handleUSDCTransaction(buttonData, notice);

                if (result) {
                    const { transactionId, receipt } = result;
                    if (receipt.status?.toString() === "SUCCESS") {
                        handleSuccess(buttonData, transactionId, notice);
                    }
                }
            });
        }); //foreach
    }

    async function fetchDataById(id) {
        try {
            const response = await fetch(`${myButtonData.restUrl}?id=${id}`, {
                method: "GET",
                headers: {
                    "X-WP-Nonce": myButtonData.nonce, // Nonce from wp_localize_script
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.error) {
                console.error(`Error fetching data for ID ${id}: ${data.error}`);
                return;
            }

            console.log(`Fetched data for ID ${id}:`, data);
            console.log(typeof data);

            return data;
        } catch (error) {
            // Handle errors
            console.error(`Error fetching data for ID ${id}:`, error);
        }
    }

    function handleSuccess(buttonData, transactionId, notice) {
        updateNotice(notice, "Transaction successful!");

        if (buttonData.store === "true") {
            // todo: store transaction id using a rest update route
            // we need transaction id..
            console.log(transactionId);
        }
    }

    async function ensureCorrectNetwork(network, notice) {
        if (window.pairingData && window.pairingData.network !== network) {
            updateNotice(notice, "You're connected to the wrong network. Please reload and try again.");
            await window.hashconnect.disconnect();
            throw new Error("Wrong network connected");
        }
        if (!window.pairingData) {
            await window.initializeHashconnect(network);
        }
    }

    async function handleUSDCTransaction(buttonData, notice) {
        const network = buttonData.network;

        const currency = buttonData.currency;
        const amount = buttonData.amount;
        const usdcAmount = await convertCurrencyToUSDC(amount, currency);

        const fromAccount = AccountId.fromString(window.pairingData.accountIds[0]); // assumes paired and takes first paired account id
        const toAccount = AccountId.fromString(buttonData.wallet);

        const signer = window.hashconnect.getSigner(fromAccount);

        const transaction = await new TransferTransaction()
            .addTokenTransfer(TokenId.fromString(USDC_TOKEN_MAP[network]), fromAccount, -usdcAmount) //Sending account
            .addTokenTransfer(TokenId.fromString(USDC_TOKEN_MAP[network]), toAccount, usdcAmount) //Receiving account
            .setTransactionMemo(buttonData.memo)
            .freezeWithSigner(signer);

        return await executeTransaction(transaction, notice);
    }

    async function handleHBARTransaction(button, buttonData, notice) {
        const tinybarAmount = await getTinybarAmount(button, buttonData, notice);

        if (tinybarAmount) {
            const fromAccount = AccountId.fromString(window.pairingData.accountIds[0]); // assumes paired and takes first paired account id
            const toAccount = AccountId.fromString(buttonData.wallet);

            const signer = window.hashconnect.getSigner(fromAccount);

            const transaction = await new TransferTransaction()
                .addHbarTransfer(fromAccount, Hbar.fromTinybars(-1 * tinybarAmount)) //Sending account
                .addHbarTransfer(toAccount, Hbar.fromTinybars(tinybarAmount)) //Receiving account
                .setTransactionMemo(buttonData.memo)
                .freezeWithSigner(signer);

            return await executeTransaction(transaction, notice);
        }
    }

    async function getTinybarAmount(button, buttonData, notice) {
        let amount = buttonData.amount;
        const currency = buttonData.currency;

        updateNotice(notice, "");

        if (!amount) {
            const input = button.previousElementSibling;
            const amountInputValue = input?.value.trim();
            if (!amountInputValue) {
                updateNotice(notice, "Please enter the amount.");
                return null;
            }
            amount = amountInputValue;
        }

        if (currency === "hbar") {
            return amount * 1e8;
        } else {
            try {
                return await convertCurrencyToTinybar(amount, currency);
            } catch (error) {
                console.error("Error converting to tinybars:", error);
                updateNotice(notice, "Error converting currency to tinybars. Please check the amount and try again.");
                return null;
            }
        }
    }

    async function convertCurrencyToUSDC(amount, currency) {
        try {
            const usdcPriceInCurrency = await convertPrice(currency, "usd-coin");
            return Math.round((amount / usdcPriceInCurrency) * 1e6);
        } catch (error) {
            console.error("Error converting currency to USDC:", error);
            throw new Error("Conversion to USDC failed");
        }
    }

    async function convertCurrencyToTinybar(amount, currency) {
        try {
            const hbarPriceInCurrency = await convertPrice(currency, "hedera-hashgraph");
            return Math.round((amount / hbarPriceInCurrency) * 1e8);
        } catch (error) {
            console.error("Error converting currency to tinybars:", error);
            throw new Error("Conversion to tinybars failed");
        }
    }

    async function convertPrice(fromCurrency, toCurrency) {
        if (!fromCurrency || !toCurrency) {
            throw new Error("Missing required currency parameter");
        }

        const url = `https://api.coingecko.com/api/v3/simple/price?ids=${toCurrency}&vs_currencies=${fromCurrency}`;

        try {
            const response = await fetch(url);
            const data = await response.json();
            console.log(data);
            if (!data || !data[toCurrency] || !data[toCurrency][fromCurrency]) {
                throw new Error(`Invalid response from coingecko: ${JSON.stringify(data)}`);
            }
            return data[toCurrency][fromCurrency];
        } catch (error) {
            console.error("Error fetching coingecko price:", error);
            throw error;
        }
    }

    async function executeTransaction(transaction, notice) {
        const fromAccount = AccountId.fromString(window.pairingData.accountIds[0]); // assumes paired and takes first paired account id
        const signer = window.hashconnect.getSigner(fromAccount);

        try {
            const response = await transaction.executeWithSigner(signer);
            const transactionId = response.transactionId.toString();
            const receipt = await response.getReceiptWithSigner(signer);
            return { transactionId, receipt };
        } catch (e) {
            console.log(e);
            if (e.code === 9000) {
                updateNotice(notice, "Transaction rejected by user or insufficient balance.");
            } else {
                updateNotice(notice, "Transaction failed. Please try again. ");
            }
            return null;
        }
    }
})();
