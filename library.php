<?php

/*
========================================================
LIBRARY BOOK ALLOTMENT SYSTEM
No MySQL / No Database
Uses data.json for storage
========================================================
*/


/*
========================================================
DATA FILE
========================================================
*/

$dataFile = __DIR__ . "/data.json";


/*
========================================================
CREATE DATA FILE IF NOT AVAILABLE
========================================================
*/

if (!file_exists($dataFile)) {

    $defaultData = array(
        "books" => array(),
        "students" => array(),
        "issued" => array()
    );

    file_put_contents(
        $dataFile,
        json_encode($defaultData, JSON_PRETTY_PRINT)
    );
}


/*
========================================================
READ DATA
========================================================
*/

$data = json_decode(
    file_get_contents($dataFile),
    true
);


if (!is_array($data)) {

    $data = array(
        "books" => array(),
        "students" => array(),
        "issued" => array()
    );
}


/*
========================================================
SAVE DATA FUNCTION
========================================================
*/

function saveData($file, $data)
{
    file_put_contents(
        $file,
        json_encode($data, JSON_PRETTY_PRINT),
        LOCK_EX
    );
}


/*
========================================================
HTML SECURITY FUNCTION
========================================================
*/

function clean($text)
{
    return htmlspecialchars(
        $text,
        ENT_QUOTES,
        "UTF-8"
    );
}


/*
========================================================
GENERATE NEXT ID
========================================================
*/

function getNextId($array)
{
    $max = 0;

    foreach ($array as $item) {

        if (isset($item["id"])) {

            if ((int)$item["id"] > $max) {

                $max = (int)$item["id"];
            }
        }
    }

    return $max + 1;
}


/*
========================================================
GET BOOK
========================================================
*/

function getBook($data, $id)
{
    foreach ($data["books"] as $book) {

        if ((int)$book["id"] == (int)$id) {

            return $book;
        }
    }

    return null;
}


/*
========================================================
GET STUDENT
========================================================
*/

function getStudent($data, $id)
{
    foreach ($data["students"] as $student) {

        if ((int)$student["id"] == (int)$id) {

            return $student;
        }
    }

    return null;
}


/*
========================================================
GET BOOK NAME
========================================================
*/

function getBookName($data, $id)
{
    $book = getBook($data, $id);

    if ($book != null) {

        return $book["name"];
    }

    return "Unknown Book";
}


/*
========================================================
GET STUDENT NAME
========================================================
*/

function getStudentName($data, $id)
{
    $student = getStudent($data, $id);

    if ($student != null) {

        return $student["name"];
    }

    return "Unknown Student";
}


/*
========================================================
GET STUDENT ROLL NUMBER
========================================================
*/

function getStudentRoll($data, $id)
{
    $student = getStudent($data, $id);

    if ($student != null) {

        return $student["roll"];
    }

    return "Unknown";
}


/*
========================================================
MESSAGE
========================================================
*/

$message = "";

$error = false;


/*
========================================================
ADD BOOK
========================================================
*/

if (isset($_POST["add_book"])) {

    $bookName = trim(
        $_POST["book_name"] ?? ""
    );

    $author = trim(
        $_POST["author"] ?? ""
    );

    $quantity = intval(
        $_POST["quantity"] ?? 0
    );


    if (
        $bookName == "" ||
        $author == "" ||
        $quantity <= 0
    ) {

        $message = "Please enter valid book details.";

        $error = true;

    } else {

        $newBook = array(

            "id" => getNextId(
                $data["books"]
            ),

            "name" => $bookName,

            "author" => $author,

            "quantity" => $quantity,

            "available" => $quantity
        );


        $data["books"][] = $newBook;


        saveData(
            $dataFile,
            $data
        );


        $message = "Book added successfully.";
    }
}


/*
========================================================
ADD STUDENT
========================================================
*/

if (isset($_POST["add_student"])) {

    $studentName = trim(
        $_POST["student_name"] ?? ""
    );

    $rollNo = trim(
        $_POST["roll_no"] ?? ""
    );

    $course = trim(
        $_POST["course"] ?? ""
    );


    if (
        $studentName == "" ||
        $rollNo == "" ||
        $course == ""
    ) {

        $message = "Please fill all student details.";

        $error = true;

    } else {

        $rollExists = false;


        foreach (
            $data["students"] as $student
        ) {

            if (
                strtolower($student["roll"])
                ==
                strtolower($rollNo)
            ) {

                $rollExists = true;

                break;
            }
        }


        if ($rollExists) {

            $message =
                "This roll number already exists.";

            $error = true;

        } else {

            $newStudent = array(

                "id" => getNextId(
                    $data["students"]
                ),

                "name" => $studentName,

                "roll" => $rollNo,

                "course" => $course
            );


            $data["students"][] =
                $newStudent;


            saveData(
                $dataFile,
                $data
            );


            $message =
                "Student added successfully.";
        }
    }
}


/*
========================================================
ISSUE / ALLOT BOOK
========================================================
*/

if (isset($_POST["issue_book"])) {

    $bookId = intval(
        $_POST["book_id"] ?? 0
    );

    $studentId = intval(
        $_POST["student_id"] ?? 0
    );


    $bookIndex = -1;

    $studentFound = false;


    /*
    FIND BOOK
    */

    foreach (
        $data["books"] as $index => $book
    ) {

        if (
            (int)$book["id"] ==
            $bookId
        ) {

            $bookIndex = $index;

            break;
        }
    }


    /*
    FIND STUDENT
    */

    foreach (
        $data["students"] as $student
    ) {

        if (
            (int)$student["id"] ==
            $studentId
        ) {

            $studentFound = true;

            break;
        }
    }


    /*
    CHECK BOOK
    */

    if ($bookIndex == -1) {

        $message =
            "Please select a valid book.";

        $error = true;

    }

    elseif (!$studentFound) {

        $message =
            "Please select a valid student.";

        $error = true;

    }

    elseif (
        $data["books"][$bookIndex]["available"]
        <= 0
    ) {

        $message =
            "This book is not available.";

        $error = true;

    }

    else {

        /*
        REDUCE AVAILABLE BOOK
        */

        $data["books"][$bookIndex]["available"]--;


        /*
        CREATE ISSUE RECORD
        */

        $newIssue = array(

            "id" => getNextId(
                $data["issued"]
            ),

            "book_id" => $bookId,

            "student_id" => $studentId,

            "issue_date" => date("Y-m-d"),

            "return_date" => "",

            "status" => "Issued"
        );


        $data["issued"][] =
            $newIssue;


        saveData(
            $dataFile,
            $data
        );


        $message =
            "Book allotted successfully.";
    }
}


/*
========================================================
RETURN BOOK
========================================================
*/

if (isset($_POST["return_book"])) {

    $issueId = intval(
        $_POST["issue_id"] ?? 0
    );


    $issueIndex = -1;


    /*
    FIND ISSUE
    */

    foreach (
        $data["issued"] as $index => $issue
    ) {

        if (
            (int)$issue["id"] ==
            $issueId
        ) {

            $issueIndex = $index;

            break;
        }
    }


    if ($issueIndex == -1) {

        $message =
            "Issued book record not found.";

        $error = true;

    }

    elseif (
        $data["issued"][$issueIndex]["status"]
        == "Returned"
    ) {

        $message =
            "This book has already been returned.";

        $error = true;

    }

    else {

        /*
        GET BOOK ID
        */

        $bookId =
            $data["issued"][$issueIndex]["book_id"];


        /*
        INCREASE AVAILABLE BOOK
        */

        foreach (
            $data["books"] as $index => $book
        ) {

            if (
                (int)$book["id"] ==
                (int)$bookId
            ) {

                $data["books"][$index]["available"]++;

                break;
            }
        }


        /*
        UPDATE ISSUE RECORD
        */

        $data["issued"][$issueIndex]["return_date"] =
            date("Y-m-d");


        $data["issued"][$issueIndex]["status"] =
            "Returned";


        saveData(
            $dataFile,
            $data
        );


        $message =
            "Book returned successfully.";
    }
}


/*
========================================================
DELETE BOOK
========================================================
*/

if (isset($_POST["delete_book"])) {

    $bookId = intval(
        $_POST["book_id"] ?? 0
    );


    $currentlyIssued = false;


    /*
    CHECK IF BOOK IS ISSUED
    */

    foreach (
        $data["issued"] as $issue
    ) {

        if (
            (int)$issue["book_id"] ==
            $bookId
            &&
            $issue["status"] ==
            "Issued"
        ) {

            $currentlyIssued = true;

            break;
        }
    }


    if ($currentlyIssued) {

        $message =
            "Cannot delete a book that is currently issued.";

        $error = true;

    }

    else {

        $newBooks = array();


        foreach (
            $data["books"] as $book
        ) {

            if (
                (int)$book["id"] !=
                $bookId
            ) {

                $newBooks[] = $book;
            }
        }


        $data["books"] =
            $newBooks;


        saveData(
            $dataFile,
            $data
        );


        $message =
            "Book deleted successfully.";
    }
}


/*
========================================================
CLEAR ALL DATA
========================================================
*/

if (isset($_POST["clear_data"])) {

    $data = array(

        "books" => array(),

        "students" => array(),

        "issued" => array()
    );


    saveData(
        $dataFile,
        $data
    );


    $message =
        "All data has been cleared.";
}


/*
========================================================
DASHBOARD
========================================================
*/

$totalBooks =
    count($data["books"]);


$totalStudents =
    count($data["students"]);


$totalAvailable = 0;


foreach (
    $data["books"] as $book
) {

    $totalAvailable +=
        (int)$book["available"];
}


$totalIssued = 0;


foreach (
    $data["issued"] as $issue
) {

    if (
        $issue["status"] ==
        "Issued"
    ) {

        $totalIssued++;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Library Book Allotment System
</title>

<link rel="stylesheet"
href="style.css">

</head>


<body>


<header>

<h1>
Library Book Allotment System
</h1>

</header>


<nav>

<a href="index.html">
Home
</a>

<a href="#dashboard">
Dashboard
</a>

<a href="#books">
Books
</a>

<a href="#students">
Students
</a>

<a href="#issue">
Issue Book
</a>

<a href="#returns">
Return Book
</a>

</nav>


<div class="container">


<!-- MESSAGE -->

<?php

if ($message != "") {

?>

<div class="message
<?php
if ($error) {
    echo "error";
}
?>
">

<?php

echo clean($message);

?>

</div>

<?php

}

?>


<!-- ==================================================
DASHBOARD
================================================== -->

<section id="dashboard">

<h2>
Dashboard
</h2>


<div class="dashboard">


<div class="box">

<h3>
Total Books
</h3>

<p>
<?php

echo $totalBooks;

?>
</p>

</div>


<div class="box">

<h3>
Total Students
</h3>

<p>

<?php

echo $totalStudents;

?>

</p>

</div>


<div class="box">

<h3>
Issued Books
</h3>

<p>

<?php

echo $totalIssued;

?>

</p>

</div>


<div class="box">

<h3>
Available Copies
</h3>

<p>

<?php

echo $totalAvailable;

?>

</p>

</div>


</div>

</section>



<!-- ==================================================
BOOK SECTION
================================================== -->

<section id="books">

<h2>
Add Book
</h2>


<form method="POST">

<label>
Book Name
</label>

<input
type="text"
name="book_name"
placeholder="Enter book name"
required
>


<label>
Author
</label>

<input
type="text"
name="author"
placeholder="Enter author name"
required
>


<label>
Quantity
</label>

<input
type="number"
name="quantity"
min="1"
placeholder="Enter quantity"
required
>


<button
type="submit"
name="add_book"
>

Add Book

</button>

</form>



<h2>
Book List
</h2>


<input
type="text"
id="bookSearch"
placeholder="Search book or author..."
onkeyup="searchBooks()"
>


<table id="bookTable">


<tr>

<th>
ID
</th>

<th>
Book Name
</th>

<th>
Author
</th>

<th>
Quantity
</th>

<th>
Available
</th>

<th>
Action
</th>

</tr>


<?php

foreach (
    $data["books"] as $book
) {

?>


<tr>


<td>

<?php

echo clean(
    $book["id"]
);

?>

</td>


<td>

<?php

echo clean(
    $book["name"]
);

?>

</td>


<td>

<?php

echo clean(
    $book["author"]
);

?>

</td>


<td>

<?php

echo clean(
    $book["quantity"]
);

?>

</td>


<td>

<?php

echo clean(
    $book["available"]
);

?>

</td>


<td>


<form
method="POST"
class="small-form"
onsubmit="return confirmDelete();"
>


<input
type="hidden"
name="book_id"
value="<?php

echo clean(
    $book["id"]
);

?>"
>


<button
type="submit"
name="delete_book"
>

Delete

</button>


</form>


</td>


</tr>


<?php

}

?>


</table>


</section>



<!-- ==================================================
STUDENT SECTION
================================================== -->

<section id="students">


<h2>
Add Student
</h2>


<form method="POST">


<label>
Student Name
</label>


<input
type="text"
name="student_name"
placeholder="Enter student name"
required
>


<label>
Roll Number
</label>


<input
type="text"
name="roll_no"
placeholder="Enter roll number"
required
>


<label>
Course
</label>


<input
type="text"
name="course"
placeholder="Enter course"
required
>


<button
type="submit"
name="add_student"
>

Add Student

</button>


</form>



<h2>
Student List
</h2>


<table>


<tr>

<th>
ID
</th>

<th>
Name
</th>

<th>
Roll Number
</th>

<th>
Course
</th>

</tr>


<?php

foreach (
    $data["students"] as $student
) {

?>


<tr>


<td>

<?php

echo clean(
    $student["id"]
);

?>

</td>


<td>

<?php

echo clean(
    $student["name"]
);

?>

</td>


<td>

<?php

echo clean(
    $student["roll"]
);

?>

</td>


<td>

<?php

echo clean(
    $student["course"]
);

?>

</td>


</tr>


<?php

}

?>


</table>


</section>



<!-- ==================================================
ISSUE BOOK
================================================== -->

<section id="issue">


<h2>
Issue / Allot Book
</h2>


<form method="POST">


<label>
Select Book
</label>


<select
name="book_id"
required
>


<option value="">
-- Select Book --
</option>


<?php

foreach (
    $data["books"] as $book
) {


if (
    (int)$book["available"] > 0
) {

?>


<option
value="<?php

echo clean(
    $book["id"]
);

?>"
>


<?php

echo clean(
    $book["name"]
);

?>


-

Available:

<?php

echo clean(
    $book["available"]
);

?>


</option>


<?php

}

}

?>


</select>



<label>
Select Student
</label>


<select
name="student_id"
required
>


<option value="">
-- Select Student --
</option>


<?php

foreach (
    $data["students"] as $student
) {

?>


<option
value="<?php

echo clean(
    $student["id"]
);

?>"
>


<?php

echo clean(
    $student["name"]
);

?>


-

<?php

echo clean(
    $student["roll"]
);

?>


</option>


<?php

}

?>


</select>



<button
type="submit"
name="issue_book"
>

Issue / Allot Book

</button>


</form>


</section>



<!-- ==================================================
RETURN BOOK
================================================== -->

<section id="returns">


<h2>
Issued / Returned Books
</h2>


<table>


<tr>

<th>
ID
</th>

<th>
Book
</th>

<th>
Student
</th>

<th>
Roll No
</th>

<th>
Issue Date
</th>

<th>
Return Date
</th>

<th>
Status
</th>

<th>
Action
</th>

</tr>


<?php

foreach (
    $data["issued"] as $issue
) {


$book =
    getBook(
        $data,
        $issue["book_id"]
    );


$student =
    getStudent(
        $data,
        $issue["student_id"]
    );

?>


<tr>


<td>

<?php

echo clean(
    $issue["id"]
);

?>

</td>


<td>

<?php

if ($book != null) {

    echo clean(
        $book["name"]
    );

} else {

    echo "Unknown Book";
}

?>

</td>


<td>

<?php

if ($student != null) {

    echo clean(
        $student["name"]
    );

} else {

    echo "Unknown Student";
}

?>

</td>


<td>

<?php

if ($student != null) {

    echo clean(
        $student["roll"]
    );

} else {

    echo "-";
}

?>

</td>


<td>

<?php

echo clean(
    $issue["issue_date"]
);

?>

</td>


<td>

<?php

echo clean(
    $issue["return_date"]
);

?>

</td>


<td>

<?php

echo clean(
    $issue["status"]
);

?>

</td>


<td>


<?php

if (
    $issue["status"] ==
    "Issued"
) {

?>


<form
method="POST"
class="small-form"
onsubmit="return confirmReturn();"
>


<input
type="hidden"
name="issue_id"
value="<?php

echo clean(
    $issue["id"]
);

?>"
>


<button
type="submit"
name="return_book"
>

Return

</button>


</form>


<?php

} else {

echo "Completed";

}

?>


</td>


</tr>


<?php

}

?>


</table>


</section>



<!-- ==================================================
CLEAR DATA
================================================== -->

<section>


<h2>
System Management
</h2>


<form
method="POST"
onsubmit="return clearData();"
>


<button
type="submit"
name="clear_data"
>

Clear All Data

</button>


</form>


</section>


</div>


<script src="script.js"></script>


</body>

</html>