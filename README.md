# minecraft-itemprice

This Web App allows you to monitor Itemprices on a Minecraft Server

# Setup

## Setting up the Environment Variables

- SetEnv DB_DRIVER mysqli
- SetEnv DB_HOST localhost
- SetEnv DB_LOGIN root
- SetEnv DB_PASSWORD ""
- SetEnv DB_DATABASE forum
- SetEnv DB_PREFIX ""
- SetEnv DB_ENCODING ""

## Install dependencies for TailwindCSS

cd into vendors/Tailwind

```bash
npm i
```

# Development

cd into vendors/Tailwind

```bash
npm run css
```

# TODO

- [x] Setting up a php app with the Kata Framework

- [x] Initial Page to set Root Account

  - [ ] Enable account verification for default users
  - [x] Setup Databases and Tables

- [x] Admin Panel to Create Accounts
- [x] Panel to create Shops and Auctions
- [ ] Main Page to view and Compare Auctions (listing of Items)
- [ ] Page to analyse every Item
- [ ] Secured API to Insert Auctions

# Info

## webroot/data/1-18-textures.json

Got the Datalist from [PrismarineJS/minecraft-assets](https://github.com/PrismarineJS/minecraft-assets)

[texture_content.json](https://github.com/PrismarineJS/minecraft-assets/blob/master/data/1.18.1/texture_content.json)
