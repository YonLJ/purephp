const handleAdd = () => {
  let output = document.querySelector("#output");
  let result = +output.innerText + 1;

  output.innerText = result;
};

document.querySelector("#subtract")
  .addEventListener("click", () => {
    let output = document.querySelector("#output");
    let result = +output.innerText - 1;

    output.innerText = result;
  });
