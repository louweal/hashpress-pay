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

                const network = payButton.dataset.network;
                await ensureCorrectNetwork(network, notice);

                const acceptedCurrency = payButton.dataset.accepts.toUpperCase();

                let result =
                    acceptedCurrency === "HBAR"
                        ? await handleHBARTransaction(payButton, notice)
                        : await handleUSDCTransaction(payButton, notice);

                if (result) {
                    const { transactionId, receipt } = result;
                    if (receipt.status?.toString() === "SUCCESS") {
                        handleSuccess(payButton, transactionId, notice);
                    }
                }
            });
        }); //foreach
    }

    // Function to toggle the shortcode display based on payment method selection
    function toggleCustomPaymentShortcode() {
        const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked');
        const shortcodeContainer = document.getElementById("hashpress-pay-hbar");
        if (!selectedPaymentMethod || !shortcodeContainer) return;

        if (selectedPaymentMethod.value === "hashpress-pay-hbar") {
            shortcodeContainer.classList.add("is-active");
        } else {
            shortcodeContainer.classList.remove("is-active");
        }
    }

    function handleSuccess(button, transactionId, notice) {
        updateNotice(notice, "Transaction successful!");

        if (button.dataset.store === "true") {
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

    async function handleUSDCTransaction(button, notice) {
        const network = button.dataset.network;
        // const balance = await getUSDCBalance(network, USDC_TOKEN_MAP[network], window.pairingData.accountIds[0]);

        const currency = button.dataset.currency;
        const amount = button.dataset.amount;
        const usdcAmount = await convertCurrencyToUSDC(amount, currency);

        // if (usdcAmount <= balance) {

        const fromAccount = AccountId.fromString(window.pairingData.accountIds[0]); // assumes paired and takes first paired account id
        const toAccount = AccountId.fromString(button.dataset.wallet);

        const signer = window.hashconnect.getSigner(fromAccount);

        const transaction = await new TransferTransaction()
            .addTokenTransfer(TokenId.fromString(USDC_TOKEN_MAP[network]), fromAccount, -usdcAmount) //Sending account
            .addTokenTransfer(TokenId.fromString(USDC_TOKEN_MAP[network]), toAccount, usdcAmount) //Receiving account
            .setTransactionMemo(button.dataset.memo)
            .freezeWithSigner(signer);

        return await executeTransaction(transaction, notice);
        // } else {
        //     console.log("Insufficient USDC balance.");
        //     updateNotice(notice, "Insufficient USDC balance.");
        // }
    }

    async function handleHBARTransaction(button, notice) {
        const tinybarAmount = await getTinybarAmount(button, notice);

        if (tinybarAmount) {
            const fromAccount = AccountId.fromString(window.pairingData.accountIds[0]); // assumes paired and takes first paired account id
            const toAccount = AccountId.fromString(button.dataset.wallet);

            const signer = window.hashconnect.getSigner(fromAccount);

            const transaction = await new TransferTransaction()
                .addHbarTransfer(fromAccount, Hbar.fromTinybars(-1 * tinybarAmount)) //Sending account
                .addHbarTransfer(toAccount, Hbar.fromTinybars(tinybarAmount)) //Receiving account
                .setTransactionMemo(button.dataset.memo)
                .freezeWithSigner(signer);

            return await executeTransaction(transaction, notice);
        } else {
            // updateNotice(notice, "Insufficient HBAR balance or error in amount conversion.");
        }
    }

    // async function getUSDCBalance(network, tokenId, accountId) {
    //     let url = `https://${network}.mirrornode.hedera.com/api/v1/accounts/${accountId}`;

    //     try {
    //         const response = await fetch(url);
    //         const data = await response.json();

    //         const tokens = data?.balance?.tokens || [];
    //         const tokenData = tokens.find((token) => token.token_id === tokenId);
    //         return tokenData ? tokenData.balance : 0;
    //     } catch (err) {
    //         console.error("Error fetching USDC balance:", err);
    //         return 0;
    //     }
    // }

    async function getTinybarAmount(button, notice) {
        let amount = button.dataset.amount;
        const currency = button.dataset.currency;

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
