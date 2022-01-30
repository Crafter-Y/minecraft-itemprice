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

        let entrys = document.getElementsByClassName("entry")
        for (entry of entrys) {
            let item = entry.children[1].innerText
            let texture = data.find(x => x.name === item);
            entry.children[0].firstElementChild.setAttribute("src", texture.texture);
            entry.children[1].innerText = toCamelCase(item.replace("_", " "));
            entry.children[2].innerText = formatPrice(entry.children[2].innerText) + "/pc."
        }

    }
}

main()