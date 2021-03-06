<?php
        require("./inc/header.inc.php");
        if(!isAdmin($_SESSION['username'])){
          showModalRedirect("ERROR", "Fehler", "Der Zugriff auf diese Seite wurde verweigert.", "index.php");
          exit;
        }
        ?>
        <div class="flex-container animated fadeIn">
          <div class="flex item-1">
            <?php
            if (isset($_GET["delete"]) && isset($_GET["id"])) {
                $stmt = MySQLWrapper()->prepare("SELECT * FROM reasons WHERE ID = :id");
                $stmt->bindParam(":id", $_GET['id'], PDO::PARAM_INT);
                $stmt->execute();
                $row = $stmt->fetch();
                if (!empty($row)) {
                    $stmt = MySQLWrapper()->prepare("DELETE FROM reasons WHERE ID = :id");
                    $stmt->bindParam(":id", $_GET['id'], PDO::PARAM_INT);
                    $stmt->execute();
                    $idstmt = MySQLWrapper()->prepare("SELECT * FROM reasons");
                    $idstmt->execute();
                    $id = 0;
                    try {
                    MySQLWrapper()->beginTransaction();
                    $stmt = MySQLWrapper()->prepare("UPDATE reasons SET ID = :id WHERE ID = :dbid");
                    while ($row = $idstmt->fetch()) {
                        $id++;
                        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                        $stmt->bindParam(":dbid", $row["ID"], PDO::PARAM_INT);
                        $stmt->execute();
                    }
                    MySQLWrapper()->commit();
                    } catch (Exception $e) {
                    MySQLWrapper()->rollBack();
                    showModal("ERROR", "Fehler", "Es ist ein fehler aufgetreten.");
                    }

                    showModalRedirect("SUCCESS", "Erfolgreich", "Der Bangrund wurde erfolgreich gelöscht.", "reasons.php");
                } else {
                    showModal("ERROR", "Fehler", "Der angeforderte Bangrund wurde nicht gefunden.");
                }
            }

            ?>
             <script>
               $(function() {
                  $( "tbody" ).sortable({
                    axis: 'y',
                    update: function (event, ui) {
                      var data = $(this).sortable('serialize');
                      // POST to server using $.post or $.ajax
                      $.ajax({
                        data: data,
                        type: 'POST',
                        url: 'fetch.php?type=SORTINDEX'
                      });
                    }
                  }
                  );
                });
             </script>
            <h1>Bangründe</h1>
            <table> 
              <tr>
                <th>ID</th>
                <th>Bangrund</th>
                <th>Zeit</th>
                <th>Type</th>
                <th>hinzugefügt am</th>
                <th>Bans</th>
                <th>Permission</th>
                <th>Aktionen</th>
              </tr>
              <tbody>
                <?php
                $stmt = MySQLWrapper()->prepare("SELECT * FROM reasons ORDER BY SORTINDEX ASC");
                $stmt->execute();
                while($row = $stmt->fetch()){
                  echo '<tr id="item-'.$row["ID"].'">';
                  echo '<td>'.$row["ID"].'</td>';
                  echo '<td>'.htmlspecialchars($row["REASON"]).'</td>';
                  if($row["TIME"] == -1){
                    echo "<td>Permanent</td>";
                  } else if($row["TIME"] < 60){
                    echo '<td>'.$row["TIME"].' Minuten</td>';
                  } else if($row["TIME"] < 1440){
                    $stunden = $row["TIME"] / 60;
                    echo '<td>'.$stunden.' Stunden</td>';
                  } else {
                    $tage = $row["TIME"] / 1440;
                    echo '<td>'.$tage.' Tage</td>';
                  }
                  if($row["TYPE"] == 0){
                    echo '<td>BAN</td>';
                  } else {
                    echo '<td>MUTE</td>';
                  }
                  echo '<td>'.date('d.m.Y H:i',$row["ADDED_AT"]).'</td>';
                  echo '<td>'.$row["BANS"].'</td>';
                  if($row["PERMS"] == "null"){
                    echo '<td>Keine</td>';
                  } else {
                    echo '<td>'.$row["PERMS"].'</td>';
                  }
                  echo '<td><a href="editreason.php?id='.$row["ID"].'"><i class="material-icons">edit</i></a> ';
                  echo '<a href="reasons.php?delete&id='.$row["ID"].'"><i class="material-icons">block</i></a></td>';
                  echo '</tr>';
                }
                 ?>
              </tbody>
              
            </table>
          </div>
          <div class="flex item-2 sidebox">
            <?php
            if(isset($_POST["submit"]) && isset($_SESSION["CSRF"])){
              if($_POST["CSRFToken"] != $_SESSION["CSRF"]){
                showModal("ERROR", "CSRF Fehler", "Deine Sitzung ist abgelaufen. Versuche die Seite erneut zu öffnen.");
              } else {
                $id = 0;
                if(isset($_POST["id"])){
                  $id = $_POST["id"];
                } else {
                  $id = countReasons() + 1;
                }
                if(filter_var($_POST['zeit'], FILTER_VALIDATE_INT)){
                  $zeit = $_POST['zeit'];
                } else {
                  showModalRedirect("ERROR", "Fehler", "Du hast keine gültige Zahl angegeben.", "reasons.php");
                  exit;
                }
                if(getReasonByReasonID($id) == null){
                  if($_POST["einheit"] == "m"){
                    $minuten = $zeit;
                  } else if($_POST["einheit"] == "s"){
                    $minuten = $zeit * 60;
                  } else if($_POST["einheit"] == "t"){
                    $minuten = $zeit * 60 * 24;
                  }
                  if($_POST["type"] == "ban"){
                    $type = 0;
                  } else if($_POST["type"] == "mute"){
                    $type = 1;
                  } else if($_POST["type"] == "permaban"){
                    $type = 0;
                    $minuten = -1;
                  } else if($_POST["type"] == "permamute"){
                    $type = 1;
                    $minuten = -1;
                  }
                  $stmt = MySQLWrapper()->prepare("SELECT * FROM reasons WHERE REASON = :grund");
                  $stmt->bindParam(":grund", $_POST['grund'], PDO::PARAM_STR);
                  $stmt->execute();
                  $count = $stmt->rowCount();
  
                  if($count == 0){
                    $uhrzeit = time();
                    $stmt = MySQLWrapper()->prepare("INSERT INTO reasons (ID, REASON, TIME, TYPE, ADDED_AT, BANS, PERMS, SORTINDEX) VALUES (:id, :grund, :min, :type, :now, 0, :perms, :id)");
                    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                    $stmt->bindParam(":grund", $_POST['grund'], PDO::PARAM_STR);
                    $stmt->bindParam(":min", $minuten, PDO::PARAM_INT);
                    $stmt->bindParam(":type", $type, PDO::PARAM_INT);
                    $stmt->bindParam(":now", $uhrzeit, PDO::PARAM_INT);
                    if($_POST["perms"] != ""){
                      $perms = $_POST["perms"];
                    } else {
                      $perms = "null";
                    }
                    $stmt->bindParam(":perms", $perms, PDO::PARAM_STR);
                    $stmt->execute();
                    showModalRedirect("SUCCESS", "Erfolgreich", "Der Grund <strong>".htmlspecialchars($_POST["grund"])."</strong> wurde erfolgreich hinzugefügt.", "reasons.php");
                } else {
                  showModal("ERROR", "Fehler", "Diese ID ist bereits registriert.");
                }
                
              } else {
                showModal("ERROR", "Fehler", "Dieser Grund ist bereits registriert.");
              }
               
              }
            } else {
              //Erstelle Token wenn Formular nicht abgesendet wurde
              $_SESSION["CSRF"] = generateRandomString(25);
            }
             ?>
            <h1>Bangrund erstellen</h1>
            <form action="reasons.php" method="post">
              <input type="hidden" name="CSRFToken" value="<?php echo $_SESSION["CSRF"]; ?>">
              <input type="number" name="id" placeholder="ID" value="<?php echo countReasons() + 1 ?>" require><br>
              <input type="text" name="grund" placeholder="Grund" required><br>
              <input type="number" name="zeit" placeholder="Dauer" required><br>
              <input type="text" name="perms" placeholder="Permission (optional)"><br>
              <select name="einheit">
                <option value="m">Minuten</option>
                <option value="s">Stunden</option>
                <option value="t">Tage</option>
              </select><br>
              <select name="type">
                <option value="ban">Ban</option>
                <option value="mute">Mute</option>
                <option value="permaban">Permanenter Ban</option>
                <option value="permamute">Permanenter Mute</option>
              </select><br>
              <button type="submit" name="submit">Grund hinzufügen</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>