const webpack = require("webpack");
const path = require("path");

module.exports = {
    entry: "./src/js/main.js",
    output: {
        path: path.resolve(__dirname),
        filename: "./dist/[name].bundle.js",
        publicPath: "/wp-content/plugins/hashpress-pay/",
    },
    devServer: {
        static: {
            directory: path.join(__dirname, "/"),
        },
    },
    mode: "development",
    devtool: false,
    resolve: {
        extensions: [".js"],
        fallback: {
            // buffer: require.resolve("buffer/"), // Path to the buffer module
            // stream: require.resolve("stream-browserify"), // Fallback for stream
            // process: require.resolve("process/browser.js"), // Fallback for process
            // https: require.resolve(`https-browserify`),
            // http: require.resolve(`stream-http`),
            // crypto: false,
            // http2: false,
            // // stream: false,
            // util: false,
            // path: false,
            // os: false,
            // fs: false,
            // dns: false,
            // net: false,
            // zlib: false,
            // url: false,
            // tls: false,
        },
    },
    plugins: [
        // new webpack.ProvidePlugin({
        //     process: "process/browser.js", // Polyfill for process
        //     Buffer: ["buffer", "Buffer"], // Polyfill for Buffer if needed
        // }),
    ],
    module: {
        rules: [
            // {
            //     test: /\.js$/,
            //     exclude: /node_modules/,
            //     use: "babel-loader",
            // },
        ],
    },
    optimization: {
        splitChunks: {
            chunks: "all", // Split all chunks
            cacheGroups: {
                default: false, // Disable the default 'commons' chunk
                vendors: {
                    test: /[\\/]node_modules[\\/]/,
                    name: "vendors",
                    chunks: "all",
                },
            },
        },
    },
};
