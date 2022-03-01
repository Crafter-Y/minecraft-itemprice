function toCamelCase(str) {
    return str.replace(/(?:^\w|[A-Z]|\b\w)/g, function (word, index) {
        return word.toUpperCase()
    })
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
            name = name.replaceAll("_", " ");
            name = toCamelCase(name);

            option.innerText = name;
            selectList.appendChild(option);
        }
    }
}

main();