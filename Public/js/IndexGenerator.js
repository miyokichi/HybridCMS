window.onload = function() {
    var sectionTitleElements = document.getElementsByClassName("section-title");
    var index = document.getElementById("index");

    for (let i = 0; i < sectionTitleElements.length; i++) {
        var sectionTitleElement = sectionTitleElements[i];
        sectionTitleElement.setAttribute("id", "section-title-" + i);
        var indexItem = document.createElement("a");
        indexItem.setAttribute("href", "#section-title-" + i);
        indexItem.innerHTML = sectionTitleElement.innerHTML;
        indexItem.setAttribute("class", "index-item");
        index.appendChild(indexItem);
        //console.log(sectionTitleElements)
    }
};