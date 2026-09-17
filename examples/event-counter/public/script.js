const handleAdd = () => {
    const output = document.querySelector("#output");
    const result = +output.innerText + 1;

    output.innerText = result;
};

document.querySelector("#subtract")
    .addEventListener("click", () => {
        const output = document.querySelector("#output");
        const result = +output.innerText - 1;

        output.innerText = result;
    });
