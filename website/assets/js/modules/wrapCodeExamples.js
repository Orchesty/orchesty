function BuildMenuAndWrapCodeExamples() {
  let codeBlockCount = countCodeBlocks();

  for (let i = 1; i <= codeBlockCount; i++) {
    let codeBlockName = 'main-code-block-' + i;
    let codeExampleName = 'code-block-' + i;
    let tempDivName = 'temp-div-' + i;
    let tabsName = 'tab-links';
    let tabName = 'tab-link';

    createCodeBlock(codeBlockName, codeExampleName);
    createMenu(i, codeBlockName, codeExampleName, tabsName);
    createTempDiv(codeBlockName, tempDivName);
    let wrapper = wrapCodeExample(codeExampleName);
    replaceTempDivWithWrapper(wrapper, tempDivName);

    // create frame for each examples group
    showOnlyFirstBlockExample(codeExampleName);
    setActiveTab(codeBlockName, tabName);

    createCopyButton(i, tabsName, codeExampleName);
  }
}

function countCodeBlocks() {
  let elements = document.querySelectorAll('[class^="code-block"]');

  let classNames = [];
  for (let i = 0; i < elements.length; i++) {
    classNames.push(elements[i].className);
  }

  return [...new Set(classNames)].length;
}

function createCodeBlock(codeBlockName, className) {
  let codeBlockDiv = document.createElement('div');
  codeBlockDiv.classList.add('main-code-block');
  codeBlockDiv.classList.add(codeBlockName);

  let query = '[class^="' + className + '"]';
  let firstExample = document.querySelector(query);
  firstExample.before(codeBlockDiv);
}

function createMenuItem(index, type, name) {
  let element = document.createElement('input');

  element.type = type;
  element.value = name;
  element.name = type + '-' + name;
  element.onclick = function () {
    let codeExampleName = 'code-block-' + index;
    let examples = document.getElementsByClassName(codeExampleName);
    for (let i = 0; i < examples.length; i++) {
      let lang = examples[i].getAttribute('lang');
      if (lang === name) {
        examples[i].style.display = 'block';
      } else {
        examples[i].style.display = 'none';
      }
    }
  };

  return element;
}

function createMenu(index, codeBlockName, className, tabsName) {
  // create temp div
  let menuDiv = document.createElement('div');
  menuDiv.setAttribute('class', tabsName);

  // create buttons
  let examples = document.getElementsByClassName(className);
  for (let i = 0; i < examples.length; i++) {
    let button = createMenuItem(index, 'button', examples[i].getAttribute('lang'));
    button.setAttribute('class', 'tab-link');
    if (i === 0) {
      button.classList.add('active');
    }
    menuDiv.appendChild(button);
  }

  let codeBlockDiv = document.getElementsByClassName(codeBlockName)[0];
  codeBlockDiv.appendChild(menuDiv);
}

function createTempDiv(codeBlockName, tempDivName) {
  // create temp div
  let tempDiv = document.createElement('div');
  tempDiv.setAttribute('class', tempDivName);

  let codeBlockDiv = document.getElementsByClassName(codeBlockName)[0];
  codeBlockDiv.appendChild(tempDiv);
}

function wrapCodeExample(className) {
  // collect
  let examplesCollection = document.getElementsByClassName(className);
  let examples = Array.prototype.slice.call(examplesCollection);

  // wrap
  let wrapper = document.createElement('div');
  wrapper.setAttribute('class', 'tab-contents');
  for (let i = 0; i < examples.length; i++) {
    wrapper.appendChild(examples[i]);
  }

  // remove
  while (document.getElementsByClassName(className)[0]) {
    document.getElementsByClassName(className)[0].remove();
  }

  return wrapper;
}

function replaceTempDivWithWrapper(wrapper, tempDivName) {
  let query = '[class^="' + tempDivName + '"]';
  let tempDiv = document.querySelector(query);
  tempDiv.replaceWith(wrapper);
}

function showOnlyFirstBlockExample(className) {
  let examples = document.getElementsByClassName(className);
  for (let i = 0; i < examples.length; i++) {
    examples[i].style.display = 'none';
  }
  examples[0].style.display = 'block';
}

function switchActiveClass(e) {
  let codeBlock = document.getElementsByClassName(e.currentTarget.codeBlockName);
  let currentActive = codeBlock[0].getElementsByClassName('active');
  currentActive[0].className = currentActive[0].className.replace(' active', '');
  this.classList.add('active');
}

function setActiveTab(codeBlockName, tabName) {
  let buttons = document.getElementsByClassName(codeBlockName)[0].getElementsByClassName(tabName);
  for (let i = 0; i < buttons.length; i++) {
    buttons[i].addEventListener('click', switchActiveClass, false);
    buttons[i].codeBlockName = codeBlockName;
  }
}

function createCopyButton(index, tabsName, codeExampleName) {
  let copyBtn = document.createElement('button');
  copyBtn.setAttribute('class', 'copy-button');
  copyBtn.onclick = function () {
    let elements = document.getElementsByClassName(codeExampleName);
    let element;

    for (let i = 0; i < elements.length; i++) {
      let testedElement = elements[i];
      if (testedElement.style.display === 'block') {
        element = testedElement;
      }
    }

    if (document.body.createTextRange) {
      // IE
      let rangeIE = document.body.createTextRange();
      rangeIE.moveToElementText(element);
      rangeIE.select();
      document.execCommand('Copy');
    } else if (window.getSelection) {
      // other browsers
      let selection = window.getSelection();
      let range = document.createRange();
      range.selectNodeContents(element);
      selection.removeAllRanges();
      selection.addRange(range);
      document.execCommand('Copy');
    }
  };

  let codeBlockDiv = document.getElementsByClassName(tabsName)[index - 1];
  codeBlockDiv.appendChild(copyBtn);

  copyBtn.innerHTML = `
        <svg class="copy-icon" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
            <path d="M464 0H144c-26.51 0-48 21.49-48 48v48H48c-26.51 0-48 21.49-48 48v320c0 26.51 21.49 48 48 48h320c26.51 0 48-21.49 48-48v-48h48c26.51 0 48-21.49 48-48V48c0-26.51-21.49-48-48-48zM362 464H54a6 6 0 0 1-6-6V150a6 6 0 0 1 6-6h42v224c0 26.51 21.49 48 48 48h224v42a6 6 0 0 1-6 6zm96-96H150a6 6 0 0 1-6-6V54a6 6 0 0 1 6-6h308a6 6 0 0 1 6 6v308a6 6 0 0 1-6 6z"></path>
        </svg>`;
}