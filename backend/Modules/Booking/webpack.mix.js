const dotenvExpand = require("dotenv-expand");
const path = require("path");
dotenvExpand(
    require("dotenv").config({ path: "../../.env" /*, debug: true*/ })
);

const mix = require("laravel-mix");
require("laravel-mix-merge-manifest");

mix.setPublicPath("../../public")
    .mergeManifest()
    .webpackConfig({
        resolve: {
            alias: {
                "@": path.resolve(__dirname, "../../resources/js"),
                svelte: path.resolve(__dirname, "../../node_modules/svelte"),
                "svelte/internal": path.resolve(__dirname, "../../node_modules/svelte/internal/index.mjs"),
                "svelte/store": path.resolve(__dirname, "../../node_modules/svelte/store/index.mjs"),
            },
            extensions: [".*", ".wasm", ".mjs", ".js", ".jsx", ".json", ".vue"],
            mainFields: ["svelte", "browser", "module", "main"],
        },
    });

mix.js(
    __dirname + "/Resources/assets/js/app.js",
    "modules/Booking/script.js"
)
    .vue()
    .sourceMaps()
    .sass(
        __dirname + "/Resources/assets/sass/app.scss",
        "modules/Booking/style.css"
    );

if (mix.inProduction()) {
    mix.version();
}
