const renbang = document.querySelector("#toggle-btn");

renbang.addEventListener("click", function(){
    document.querySelector("#sidebar").classList.toggle("expand");
})