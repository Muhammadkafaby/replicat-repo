import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css", // Tailwind CSS (Modern)
                "resources/sass/app-clean.scss", // Clean SASS (Legacy)
                "resources/js/app.js", // Main JS
            ],
            refresh: true,
        }),
    ],
});
