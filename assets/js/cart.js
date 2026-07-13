/* ==========================================================
   SØLID - Cart
========================================================== */

document.addEventListener("DOMContentLoaded", () => {

    const minusButtons = document.querySelectorAll(".minus");
    const plusButtons = document.querySelectorAll(".plus");
    const removeButtons = document.querySelectorAll(".remove");

    const subtotal = document.getElementById("subtotal");
    const total = document.getElementById("total");

    function format(value){

        return value.toLocaleString("pt-BR",{

            style:"currency",

            currency:"BRL"

        });

    }

    function calculate(){

        let finalTotal = 0;

        document.querySelectorAll(".cart-item").forEach(item=>{

            const input = item.querySelector("input");

            const priceElement = item.querySelector(".card-price");

            const price = Number(

                priceElement.innerText
                .replace("R$","")
                .replace(/\./g,"")
                .replace(",",".")
                .trim()

            );

            finalTotal += price * Number(input.value);

        });

        subtotal.innerHTML = format(finalTotal);

        total.innerHTML = format(finalTotal);

    }

    plusButtons.forEach(button=>{

        button.addEventListener("click",()=>{

            const input = button.parentElement.querySelector("input");

            input.value++;

            calculate();

        });

    });

    minusButtons.forEach(button=>{

        button.addEventListener("click",()=>{

            const input = button.parentElement.querySelector("input");

            if(Number(input.value) > 1){

                input.value--;

            }

            calculate();

        });

    });

    removeButtons.forEach(button=>{

        button.addEventListener("click",()=>{

            const card = button.closest(".cart-item");

            card.style.opacity = "0";

            card.style.transform = "translateY(20px)";

            setTimeout(()=>{

                card.remove();

                calculate();

                emptyCart();

            },300);

        });

    });

    function emptyCart(){

        const items = document.querySelectorAll(".cart-item");

        if(items.length === 0){

            document.querySelector(".cart-products").innerHTML = `

                <div class="section-title">

                    <span>SHOPPING CART</span>

                    <h2>Seu carrinho está vazio</h2>

                    <p>

                        Adicione alguns produtos para começar sua compra.

                    </p>

                    <br>

                    <a href="shop.html" class="btn-primary">

                        Ir para Loja

                    </a>

                </div>

            `;

            subtotal.innerHTML = format(0);

            total.innerHTML = format(0);

        }

    }

    calculate();

});