function toCamelCase(str) {
    return str.replace(/(?:^\w|[A-Z]|\b\w)/g, function (word, index) {
        return word.toUpperCase()
    })
}

function formatPrice(str) {
    return new Number(str).toLocaleString('de-DE', { style: 'currency', currency: 'USD' });
}

function formatDaysSince(timeInMs) {
    let today = new Date();
    today.setHours(0, 0, 0, 0)
    today = today.getTime();

    let input = new Date(timeInMs)
    input.setHours(0, 0, 0, 0)
    input = input.getTime();

    let msInDay = 24 * 60 * 60 * 1000;

    let diff = Math.floor((today - input) / msInDay) 
    let out = "";
    switch (diff) {
        case 0:
            out = "today";
            break;
        case 1:
            out = "yesterday";
            break;
        default:
            out = diff + " days ago";
            break;
    }
    return out;
}

const format = async () => {
    let response = await fetch("/data/1-18-textures.json");
    if (response.ok) {
        let data = await response.json();
        for (let i = 0; i < document.getElementsByClassName("mcimage").length; i++) {
            let item = document.getElementsByClassName("mcimage")[i].getAttribute("alt");
            let texture = data.find(x => x.name === item);
            document.getElementsByClassName("mcimage")[i].setAttribute("src", texture.texture);
        }

        for (let i = 0; i < document.getElementsByClassName("price").length; i++) {
            document.getElementsByClassName("price")[i].innerText = formatPrice(document.getElementsByClassName("price")[i].innerText);
        }

        for (let i = 0; i < document.getElementsByClassName("itemText").length; i++) {
            let text = document.getElementsByClassName("itemText")[i].innerText;
            document.getElementsByClassName("itemText")[i].innerText = toCamelCase(text.replaceAll("_", " "));
        }

        for (let i = 0; i < document.getElementsByClassName("timeDifference").length; i++) {
            let text = document.getElementsByClassName("timeDifference")[i].innerText;

            let t = new Number(text.replaceAll(" ", ""))


            document.getElementsByClassName("timeDifference")[i].innerText = formatDaysSince(t);
        }
    }
}

format()
