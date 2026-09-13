/* ==========================================
   SEARCH BOOKS
========================================== */

function searchBooks() {

    let input =
        document.getElementById("bookSearch");

    let filter =
        input.value.toLowerCase();

    let table =
        document.getElementById("bookTable");

    let rows =
        table.getElementsByTagName("tr");


    for (let i = 1; i < rows.length; i++) {

        let text =
            rows[i].textContent.toLowerCase();


        if (text.includes(filter)) {

            rows[i].style.display = "";

        } else {

            rows[i].style.display = "none";
        }
    }
}


/* ==========================================
   DELETE CONFIRMATION
========================================== */

function confirmDelete() {

    return confirm(
        "Are you sure you want to delete this book?"
    );
}


/* ==========================================
   RETURN CONFIRMATION
========================================== */

function confirmReturn() {

    return confirm(
        "Are you sure you want to return this book?"
    );
}


/* ==========================================
   CLEAR DATA CONFIRMATION
========================================== */

function clearData() {

    return confirm(
        "WARNING!\n\n" +
        "This will delete all books, students " +
        "and issue records.\n\n" +
        "Do you want to continue?"
    );
}