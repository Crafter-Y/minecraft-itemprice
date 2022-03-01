for (const shop in limitedShops) {
    let shopData = limitedShops[shop].reverse();
    console.log(shop)
    console.log(shopData)
    const canvas = document.getElementById("chart-" + shopData[0].shopId);
    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: shopData.map(x => formatDaysSince(x.timeCreated).replace(" days", "d")),
            datasets: [{
                label: `${shop} price per ${stackSize}pcs.`,
                backgroundColor: 'rgb(255, 99, 132)',
                borderColor: 'rgb(255, 99, 132)',
                data: shopData.map(x => {
                    return (x.price / x.amount) * stackSize
                }),
            }]
        },

        options: {

            responsive: true
        }
    })
}