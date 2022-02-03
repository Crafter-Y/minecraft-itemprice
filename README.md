# minecraft-itemprice

This Web App allows you to monitor Itemprices on a Minecraft Server

# Setup

## Setting up the Environment Variables

If you can, setup following Environment Variables:

- SetEnv DB_DRIVER mysqli
- SetEnv DB_HOST localhost
- SetEnv DB_LOGIN root
- SetEnv DB_PASSWORD ""
- SetEnv DB_DATABASE forum
- SetEnv DB_PREFIX ""
- SetEnv DB_ENCODING ""

Otherwise, go info config/database.php and fill the Credentials in

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

- [ ] Edit the auction Structure to include

  - [ ] Auction Lifetime
  - [ ] Creation Time/Date
  - [ ] "Not maintained" Label
  - [ ] "Reliable" Label
  - [ ] "mostly available" Label

- [ ] Default label for shop
- [ ] include lowest auction price in main/index
- [ ] include auctions in main/view

  - [ ] slideshow
  - [ ] filter options

    - [ ] newest
    - [ ] lowest

- [ ] Secured API to Insert Auctions

# Info

## webroot/data/1-18-textures.json

Got the Datalist from [PrismarineJS/minecraft-assets](https://github.com/PrismarineJS/minecraft-assets)

[texture_content.json](https://github.com/PrismarineJS/minecraft-assets/blob/master/data/1.18.1/texture_content.json)

I removed duplicate Entrys that are not needed for my Application

# Contribute

Contirbutions are closed for now.
