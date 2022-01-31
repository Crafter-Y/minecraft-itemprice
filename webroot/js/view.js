function toCamelCase(str) {
    return str.replace(/(?:^\w|[A-Z]|\b\w)/g, function (word, index) {
        return word.toUpperCase()
    })
}

function formatPrice(str) {
    return new Number(str).toLocaleString('de-DE', { style: 'currency', currency: 'USD' });
}

const main = async () => {
    let response = await fetch("/data/1-18-textures.json");
    if (response.ok) {
        let data = await response.json();

        let item = document.getElementById("item").innerText;
        let texture = data.find(x => x.name === item);

        document.getElementById("img").setAttribute("src", texture.texture);
        document.getElementById("item").innerText = toCamelCase(item.replaceAll("_", " "));

        for (let i = 0; i < document.getElementsByClassName("price").length; i++) {
            document.getElementsByClassName("price")[i].innerText = formatPrice(document.getElementsByClassName("price")[i].innerText);
        }

    }
}

main()