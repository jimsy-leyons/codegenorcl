<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <title>CodeIgniter 4 Code Generator</title>
</head>

<body>
    <?php
    include("db.php");
    ?>
    <div class="container">
        <h4>Select Tables in <span class="text-primary"><?= $database ?></span></h4>
        <small><i>select tables to generate code</i></small>
        <form method="post" action="process.php">
            <input type="hidden" name="host" id="host" value="<?= $host ?>">
            <input type="hidden" name="username" id="username" value="<?= $username ?>">
            <input type="hidden" name="password" id="password" value="<?= $password ?>">
            <input type="hidden" name="database" id="database" value="<?= $database ?>">
            <div class="form-group">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th scope="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                    <label class="form-check-label" for="selectAll">Select All</label>
                                </div>
                            </th>
                            <th scope="col">Table Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tablesQuery = oci_parse($conn, "SELECT table_name FROM user_tables");
                        oci_execute($tablesQuery);
                        while ($row = oci_fetch_assoc($tablesQuery)) {
                            $tableName = $row['TABLE_NAME'];
                            echo '<tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="selected_tables[]" value="' . $tableName . '">
                                        </div>
                                    </td>
                                    <td>' . $tableName . '</td>
                                </tr>';
                        }
                        oci_free_statement($tablesQuery);
                        oci_close($conn);
                        ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary">Generate code</button>
        </form>
    </div>
    <script>
        // Check/Uncheck all checkboxes when "Select All" is clicked
        document.getElementById("selectAll").addEventListener("change", function() {
            var checkboxes = document.querySelectorAll('input[name="selected_tables[]"]');
            for (var checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        });
    </script>
</body>


</html>