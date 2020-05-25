var jsonFile = '{}';
var parsedJsonFile;

function ReadTextFile(file) {
  let rawFile = new XMLHttpRequest();
  rawFile.open('GET', file, false);
  rawFile.onreadystatechange = function () {
    if (rawFile.readyState === 4) {
      if (rawFile.status === 200 || rawFile.status === 0) {
        jsonFile = rawFile.responseText;
        parseJsonForLunr();
      }
    }
  };
  rawFile.send(null);
}

function RenderResults() {
  let searchResultsDiv = document.getElementById('search-results');
  let url = new URL(window.location.href);
  let searchingText = url.searchParams.get('search');

  if (!searchingText) {
    return;
  }

  const results = parsedJsonFile.search(searchingText);

  let sum = document.createTextNode('Nalezeno ' + results.length + ' výsledků.');
  let sumDiv = document.createElement('div');
  sumDiv.setAttribute('class', 'my-3');
  sumDiv.appendChild(sum);

  searchResultsDiv.appendChild(sumDiv);

  for (let i = 0; i < results.length; i++) {
    let result = results[i].ref;
    let resultFromStore = parsedJsonFile.documentStore.store[result];
    let elements = resultFromStore.elements;

    for (let k = 0; k < elements.length; k++) {
      let split = elements[k].split('||');
      if (split.length > 1) {
        let linkText = split[1];
        let text = document.createTextNode(linkText.charAt(0).toUpperCase() + linkText.slice(1));

        let row = document.createElement('div');
        row.setAttribute('class', 'my-3');
        let link = document.createElement('a');
        link.setAttribute('href', split[0]);
        link.appendChild(text);

        row.appendChild(document.createTextNode(i + 1 + '. '));
        row.appendChild(link);

        searchResultsDiv.appendChild(row);
      }
    }
  }
}

function DoInputSearch(ele) {
  if (event.keyCode === 13) {
    doSearch(ele.value);
  }
}

function DoButtonSearch() {
  let searchInput = document.querySelectorAll('.search-input');

  searchInput.forEach(function (element) {
    if (element.value !== '') {
      doSearch(element.value);
    }
  });
}

function parseJsonForLunr() {
  parsedJsonFile = lunr.Index.load(JSON.parse(jsonFile));
}

function doSearch(value) {
  window.location.href = '/docs/cs/search/search/?search=' + value;
}