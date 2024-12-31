<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Calculator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .calculator {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            color: #007BFF;
        }
        input[type="number"], select {
            width: calc(100% - 20px);
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .result {
            margin-top: 20px;
            font-size: 18px;
            color: #28a745;
        }
    </style>
</head>
<body>

<div class="calculator">
    <h2>PHP Calculator</h2>

    <form method="POST" action="">
        <input type="number" name="num1" required placeholder="Enter first number">
        <input type="number" name="num2" placeholder="Enter second number">
        <select name="operation" required>
            <option value="">Select Operation</option>
            <option value="add">Add</option>
            <option value="subtract">Subtract</option>
            <option value="multiply">Multiply</option>
            <option value="divide">Divide</option>
            <option value="power">Power</option>
            <option value="sqrt">Square Root</option>
        </select>
        <button type="submit">Calculate</button>
    </form>

    <?php
    function add($a, $b) {
        return $a + $b;
    }

    function subtract($a, $b) {
        return $a - $b;
    }

    function multiply($a, $b) {
        return $a * $b;
    }

    function divide($a, $b) {
        if ($b == 0) {
            return "Cannot divide by zero.";
        }
        return $a / $b;
    }

    function power($a, $b) {
        return pow($a, $b);
    }

    function squareRoot($a) {
        return sqrt($a);
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $num1 = $_POST['num1'];
        $num2 = $_POST['num2'];
        $operation = $_POST['operation'];

        $result = null;

        switch ($operation) {
            case 'add':
                $result = add($num1, $num2);
                break;
            case 'subtract':
                $result = subtract($num1, $num2);
                break;
            case 'multiply':
                $result = multiply($num1, $num2);
                break;
            case 'divide':
                $result = divide($num1, $num2);
                break;
            case 'power':
                $result = power($num1, $num2);
                break;
            case 'sqrt':
                $result = squareRoot($num1);
                $num2 = ''; // Avoid showing second number
                break;
        }

        echo "<div class='result'>Result: $result</div>";
    }
    ?>
</div>

</body>
</html>