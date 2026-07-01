# Changelog

## [0.4.0](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/compare/v0.3.0...v0.4.0) (2026-07-01)


### Features

* add devcontainer + Playwright MCP ([969e683](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/969e6837fd1921bf1328428dc9f2e47efa3df7d7))
* add devcontainer + Playwright MCP ([43b4509](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/43b450999a2fbc1b68c45852d68573f59363eb15))
* **component:** validate query with Symfony Validator (NotBlank + Length 500) ([82ee1ae](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/82ee1aee60b7042c0eed6b29f43d1ad02013628c))
* **component:** validate query with Symfony Validator (NotBlank + Length 500) ([1f58885](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/1f58885398d95d60f83b587fb48aa79cd11d8786))
* **rate-limit:** make the rate-limit key resolver pluggable ([2738d07](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/2738d076710c304e19b1ee5b508146773d00ae5d))
* **rate-limit:** make the rate-limit key resolver pluggable ([a2b9224](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/a2b922409c4b8742ac46069e443371e1a8d7765d))


### Bug Fixes

* **security:** enforce enabled_grids whitelist in GridQueryProcessor ([04059b8](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/04059b87cab3d017bec5fbcf26a07b17d630fca2))
* **security:** enforce enabled_grids whitelist in GridQueryProcessor ([1f44d1f](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/1f44d1f4055a8eb40b376366978534a6fad65376))
* **security:** route Live Component under admin firewall ([cfd1846](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/cfd1846beb4d3a752b62d47e3cec7b8b0d5e78bb))
* **security:** route Live Component under admin firewall ([182f218](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/182f218ee23f5a67185ebda5d1c75548612f02c5))
* **test:** align sort direction test with skip behavior from [#29](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/issues/29) ([27c7f0a](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/27c7f0a0cf6560fb70f61afbc2f72f346c70c0c8))
* **test:** update sort direction test to match skip behavior introduced by [#29](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/issues/29) ([973e650](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/973e6509f73445ae1a436c0d8a89484716314170))
* **validator:** skip invalid sort directions instead of forcing asc ([e417b2a](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/e417b2a9e6a61d8710d82f8d63ad7374dd2ff880))
* **validator:** skip invalid sort directions instead of forcing asc ([586d084](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/586d084e0cbe83f5551658b425a4a43972a2638c))

## [0.3.0](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/compare/v0.2.0...v0.3.0) (2026-05-14)


### Features

* **grid-assistant:** add opt-in grid and opt-out filter/field configuration ([57701ac](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/57701ac8d058db5daadab525f85a48b17309754f))
* **grid-assistant:** add opt-in grid and opt-out filter/field options ([4f05f40](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/4f05f4061a8b02264fd8fb2989d4fbf93727a80d))
* **resolver:** tolerate missing AI platform at install time with clear runtime error ([a2d1cf1](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/a2d1cf1422a1fa214b5318750e065cf7efce3caa))
* **resolver:** tolerate missing AI platform at install time with clear runtime error ([0aa505a](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/0aa505a9f0356acf665240454f38945936bb0d16))
* **schema:** add filter value formatter layer ([b2767c9](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/b2767c9c63861bf10a5b700c144976da7eae5cb5))


### Bug Fixes

* **behat:** freeze clock and use absolute dates for stable LLM fixtures ([87158b2](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/87158b2983c21606f5518de193026e59568cc45c))
* **behat:** freeze clock and use absolute dates for stable LLM fixtures ([7b0fbdc](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/7b0fbdccac72b4aeff9d5dbe2cf05c2e2e550000))
* **deps:** pin api-platform/symfony ^4.3 to fix sylius/typeinfo issue 18878 ([a2804cd](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/a2804cd739a409a1a3de5911c431764f2daa3b95))
* **deps:** pin symfony/ai-* to dev-main to fix symfony/ai issue 2018 ([c912ed1](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/c912ed158de3943affbdf14893aabc084bc923d8))
* **deps:** pin symfony/ai-* to dev-main to fix symfony/ai issue 2018 ([0c2d868](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/0c2d868446e7db503696848402098c7e3e5c859b))
* **deps:** pin symfony/type-info &lt;7.4 to fix sylius issue 18878 ([6651c38](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/6651c38a4e928f555dce6e357cac27d42b19a764))
* **phpstan:** resolve all static analysis errors to level max ([2360fa5](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/2360fa5c7fb317563cb95a2768f1cd4b7d5c5488))

## [0.2.0](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/compare/v0.1.0...v0.2.0) (2026-04-06)


### Features

* **grid-assistant:** fix tool execution, improve AI schema and error handling ([ed0a73e](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/ed0a73edfc3c0b217e3e7dc6e3aa59f9c21f08bd))


### Bug Fixes

* **grid-assistant:** add missing shop assets stubs for webpack build ([2fe5537](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/2fe5537558ca78fc4e9aac657e08acd8f5e46bbf))
* **grid-assistant:** disable Behat tests (no scenarios defined) ([5b899c1](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/5b899c14fdf7df8a29c582137ff636a67504a740))
* **grid-assistant:** disable Behat tests (no scenarios defined) ([feb6f8c](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/feb6f8c33456c689a85cc747d77ec0445e147da8))
* **grid-assistant:** ignore certificate install error in CI ([95f4cbb](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/95f4cbbdb4c06733a674b79216d751aa11cc633c))
* **grid-assistant:** remove non-existent shop routes from test config ([7a8f427](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/7a8f427575e0df7b8e1056d6a9e8ef3a7f8f1623))
* **grid-assistant:** remove strict validation for dev dependencies ([5da9f1a](https://github.com/Guiziweb/GuiziwebSyliusGridAssistantPlugin/commit/5da9f1a285a11fcf82b217b4f6c0dace28968a85))
