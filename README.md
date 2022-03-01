# minecraft-itemprice

This Web App allows you to monitor Itemprices on a Minecraft Server

# Setup

## Setting up the Environment Variables

If you can, setup following Environment Variables:

-   SetEnv DB_DRIVER mysqli
-   SetEnv DB_HOST localhost
-   SetEnv DB_LOGIN root
-   SetEnv DB_PASSWORD ""
-   SetEnv DB_DATABASE forum
-   SetEnv DB_PREFIX ""
-   SetEnv DB_ENCODING ""

Otherwise, go into config/database.php and fill the Credentials in

## Install Node.js dependencies

```bash
npm i
```

# Development

```bash
npm run css
```

# TODO for v1.1.0

-   [x] Edit the auction structure to include:

    -   [x] Creation Time/Date
    -   [x] "not maintained" label
    -   [x] "reliable" label
    -   [x] "mostly available" label
    -   [x] Default labels for shops

-   [x] implement labels
-   [x] include lowest auction price in main/index
-   [x] include auctions in main/view

-   [x] Secured API to insert auctions

# Info

## webroot/data/1-18-textures.json

Got the datalist from [PrismarineJS/minecraft-assets](https://github.com/PrismarineJS/minecraft-assets)

[texture_content.json](https://github.com/PrismarineJS/minecraft-assets/blob/master/data/1.18.1/texture_content.json)

I removed duplicate entrys that are not needed for my application.

## webroot/slick

I'm using [slick][https://kenwheeler.github.io/slick/] by [Ken Wheeler][http://kenwheeler.github.io/] to build my carousel in views/main/view.thtml

[Github Page][https://github.com/kenwheeler/slick/]

## webroot/chartjs

I'm using [Chart.js][https://www.chartjs.org/] to build the charts in views/main/view.thtml

[Github Page][https://github.com/chartjs/chart.js]

# Contribute

Contirbutions are closed for now.
