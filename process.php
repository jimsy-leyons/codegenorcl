<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <title>CodeIgniter 4 Code Generator</title>
</head>

<body>
    <?php
    require "./helpers/index.php";
    include("clearfolder.php");
    include("db.php");

    if (isset($_POST['selected_tables']) && is_array($_POST['selected_tables'])) {
        $tables = $_POST['selected_tables'];
    }
    ?>
    <div class="container">
        <h4>Generation Status of <span class="text-primary"><?= $database ?></span></h4>
        <small><i>Generated for <strong><?= count($tables) ?></strong> tables</i></small>
        <input type="hidden" name="host" id="host" value="<?= $host ?>">
        <input type="hidden" name="username" id="username" value="<?= $username ?>">
        <input type="hidden" name="password" id="password" value="<?= $password ?>">
        <input type="hidden" name="database" id="database" value="<?= $database ?>">
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th scope="col">
                        <div class="form-check">
                            <label class="form-check-label" for="selectAll">Table</label>
                        </div>
                    </th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($tables as $table) {
                    $columns = [];
                    $columnsQuery = oci_parse($conn, "SELECT column_name, data_type FROM user_tab_columns WHERE table_name = :table_name");
                    oci_bind_by_name($columnsQuery, ':table_name', $table);
                    oci_execute($columnsQuery);
                    if (!oci_execute($columnsQuery)) {
                        $e = oci_error($columnsQuery);  // For oci_parse errors
                        echo "Error executing query: " . $e['message'];
                    }
                    while ($col = oci_fetch_assoc($columnsQuery)) {
                        $columns[] = $col;
                    }
                    $table = strtolower($table);
                    echo "<tr><th>" . $table . "</th><td><ul>";
                    generateApiCode($table, $columns);
                    generateFlutterCode($table, $columns);
                    generateAngularCode($table, $columns);
                    // generateNodeCode($table,$columns);
                    echo "</ul</td></tr>";
                }
                oci_free_statement($columnsQuery);
                oci_close($conn);
                ?>
                <tr>
                    <th colspan="2">
                        <div class="alert alert-success" role="alert">
                            Code generation completed successfully.
                        </div>
                    </th>
                </tr>
            </tbody>
        </table>
        <a href="./index.php" class="btn btn-primary">Generate another</a>
    </div>
</body>


</html>