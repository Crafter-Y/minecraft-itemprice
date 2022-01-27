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
        data.sort((a, b) => a.name.localeCompare(b.name));
        let selectList = document.getElementById("selectList");
        for (let i = 0; i < data.length; i++) {
            let option = document.createElement("option");
            option.value = data[i].name;
            let name = data[i].name;
            name = name.replace("_", " ");
            name = toCamelCase(name);

            option.innerText = name;
            selectList.appendChild(option);
        }

        let table = document.getElementById("table");
        table.childNodes.forEach(node => {
            if (node.nodeType === 1) {
                let item = node.cells[1].innerText
                let texture = data.find(x => x.name === item);

                if (texture) {
                    node.cells[0].innerHTML = `<img src="${texture.texture}" alt="${texture.name}" height="32" width="32">`;
                }
                node.cells[1].innerText = toCamelCase(item.replace("_", " "));

                node.cells[3].innerText = formatPrice(node.cells[3].innerText)
                node.cells[4].innerText = formatPrice(node.cells[4].innerText)
                node.cells[5].innerText = formatPrice(node.cells[5].innerText)
            }

            //let item = node.firstChild.childNodes[1].textContent;
            //node.firstChild.childNodes[0].textContent = item;
        })
    }
}

main();