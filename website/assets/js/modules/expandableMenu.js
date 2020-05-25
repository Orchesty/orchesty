function AddButtonForExpandableMenuItems() {
  let itemsWithChildren = document.querySelectorAll(
    '.secondary-nav li.has-children'
  );

  itemsWithChildren.forEach(function (item) {
    let clickedButton = item.querySelector('button');

    clickedButton.addEventListener('click', function () {
      let clickedUl = item.querySelector('ul');
      clickedUl.toggleAttribute('clicked');
      clickedButton.toggleAttribute('clicked');

      // Collapse all active submenus
      let allChildren = document.querySelectorAll('.has-children ul');
      allChildren.forEach(function (ul) {
        if (!ul.hasAttribute('clicked')) {
          ul.classList.remove('active');
        }
      });

      // Collapse all active buttons
      let allButtons = document.querySelectorAll('.has-children button');
      allButtons.forEach(function (button) {
        if (!button.hasAttribute('clicked')) {
          button.classList.remove('active');
        }
      });

      // Expand clicked item
      clickedUl.classList.toggle('active');
      clickedUl.removeAttribute('clicked');
      clickedButton.classList.toggle('active');
      clickedButton.removeAttribute('clicked');
    });
  });
}

function SetDefaultActiveOnFirstLevel() {
  let activeItem = document.querySelectorAll('.secondary-nav li.has-children a.active');

  activeItem.forEach(function (item) {
    if (item.classList.contains('level_2')) {
      return;
    }

    let parent = item.parentElement;
    parent.querySelector('button').classList.toggle('active');
    parent.querySelector('ul').classList.toggle('active');
  });
}

function SetDefaultActiveOnSecondLevel() {
  let activeItem = document.querySelectorAll('.secondary-nav li.has-children ul li a.active');

  activeItem.forEach(function (item) {
    let parent = item.parentElement.parentElement.parentElement;
    parent.querySelector('button').classList.toggle('active');
    parent.querySelector('ul').classList.toggle('active');
  });
}
