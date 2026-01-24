# mathNEWS WordPress Plugin Collection

A collection of plugins to reform the mathNEWS article submission flow by completely reforming the WordPress publishing interface.
They provide:

- Specialized submission flow for contributors, including custom subtitle, author, and postscript fields
- Automated article tagging
- Streamlined approve/reject functionality for copyeditors
- Category restrictions for editors and above only
- Article locking for submitted/approved articles
- One-click image embeds for contributors
- and more!

mathNEWS currently uses this plugin, in conjunction with Classic Editor, to provide a seamless experience for writers and editors alike.
Some of the problems it has solved:

- Writers forgetting to sign off with pseudonyms
- Articles accidentally being left untagged
- Changes being made after approval, resulting in the need to review the new changes as well
- Editors accidentally publishing articles when trying to approve them

This project is currently developed as a monorepo, with each plugin occupying its own directory. Currently, two plugins are in development, mathNEWS Core and mathNEWS Onboarding.

## Installation

See installation instructions for each individual plugin.

## Development

To setup the dev environment, you must have Docker, NodeJS, and git installed.
You can find instructions in the documentation for the [@wordpress/env](https://www.npmjs.com/package/@wordpress/env) npm package.

1. Run `npm install` to install all dependencies
2. Run `npx wp-env start` to start the WordPress container

A dev server will start at `localhost:8888`; the default login is `admin` with password `password`.
For further instructions, see development instructions for each individual plugin.

## Licensing

See licensing information for each individual plugin.

