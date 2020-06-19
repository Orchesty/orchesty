// When the user scrolls down 20px from the top of the document, show the button
window.onscroll = function () {
  scrollFunction();
};

function scrollFunction() {
  //Get the button
  let goTop = document.getElementById('goTop');
  let distance = 50;
  if (document.body.scrollTop > distance || document.documentElement.scrollTop > distance) {
    goTop.style.display = 'block';
  } else {
    goTop.style.display = 'none';
  }
}

// When the user clicks on the button, scroll to the top of the document
function topFunction() {
  document.body.aniscrollTop = 0;
  document.documentElement.scrollTop = 0;
}