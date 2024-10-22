<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <title>CodeIgniter 4 Code Generator</title>
</head>
<body>
    <div class="container">
        <h1>CodeIgniter 4 Model Generator</h1>
        <form method="post" action="listtable.php">
            <div class="form-group">
                <label for="host">Host:</label>
                <input type="text" class="form-control" name="host" id="host" required>
            </div>

            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" name="username" id="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" class="form-control" name="password" id="password" required>
            </div>

            <div class="form-group">
                <label for="database">Database:</label>
                <input type="text" class="form-control" name="database" id="database" required>
            </div>
            <button type="submit" class="btn btn-primary">Proceed</button>
        </form>
    </div>
</body>


</html>