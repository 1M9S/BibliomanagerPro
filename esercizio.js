function countDown(){
    let contatore = 3;
    return function (){
        return --contatore;
    }
}

let contatore = countDown();
console.log(contatore());
console.log(contatore());
console.log(contatore());