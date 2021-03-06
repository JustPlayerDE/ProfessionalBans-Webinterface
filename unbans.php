        <?php
        require("./inc/header.inc.php");
        if(!isMod($_SESSION['username'])){
          showModalRedirect("ERROR", "Fehler", "Der Zugriff auf diese Seite wurde verweigert.", "index.php");
          exit;
        }
        ?>
        <?php
        if(!isset($_GET["id"])){
          ?>
          <div class="flex-container animated fadeIn">
            <div class="flex item-1">
              <?php
              $stmt = MySQLWrapper()->prepare("SELECT * FROM unbans WHERE STATUS = 0");
              $stmt->execute();
              $count = $stmt->rowCount();
              if($count != 0){
                ?>
                <h1>Offene Entbannungsanträge (<?php echo $count ?>)</h1>
                <table>
                  <tr>
                    <th>Spieler</th>
                    <th>Datum</th>
                    <th>Aktionen</th>
                  </tr>
                  <tr>
                    <?php
                    $stmt = MySQLWrapper()->prepare("SELECT * FROM unbans WHERE STATUS = 0");
                    $stmt->execute();
                    while($row = $stmt->fetch()){
                      echo "<tr>";
                      echo '<td>'.UUIDResolve($row["UUID"]).'</td>';
                      echo '<td>'.date('d.m.Y H:i',$row["DATE"]).'</td>';
                      echo '<td><a href="unbans.php?id='.$row["ID"].'""><i class="fas fa-eye"></i></a> ';
                      echo "</tr>";
                    }
                    ?>
                  </tr>
                </table>
                <?php
              } else {
                echo '<p style="color: red;">Keine offene Entbannungsanträge vorhanden</p>';
              }
              ?>
            </div>
            <div class="flex item-1">
              <h1>Alle Entbannungsanträge</h1>
              <table>
                <tr>
                  <th>Spieler</th>
                  <th>Datum</th>
                  <th>Entscheidung</th>
                  <th>Aktionen</th>
                </tr>
                <tr>
                  <?php
                  $stmt = MySQLWrapper()->prepare("SELECT * FROM unbans ORDER BY DATE DESC");
                  $stmt->execute();
                  while($row = $stmt->fetch()){
                    echo "<tr>";
                    echo '<td><a href="player.php?id='.$row["UUID"].'">'.UUIDResolve($row["UUID"]).'<a></td>';
                    echo '<td>'.date('d.m.Y H:i',$row["DATE"]).'</td>';
                    echo '<td>';
                    if($row["STATUS"] == 1){
                      echo "Ban aufgehoben";
                    } else if($row["STATUS"] == 2){
                      echo "Ban verkürzt";
                    } else if($row["STATUS"] == 3){
                      echo "Abgelehnt";
                    } else if($row["STATUS"] == 0){
                      echo "Ausstehend";
                    }
                    echo '</td>';
                    echo '<td><a href="unbans.php?id='.$row["ID"].'""><i class="fas fa-eye"></i></a> ';
                    echo "</tr>";
                  }
                   ?>
                </tr>
              </table>
            </div>
          </div>
          <?php
        } else {
          if(isset($_POST["submit"])){
            function getUUIDFromRequest($id){
              $stmt = MySQLWrapper()->prepare("SELECT * FROM unbans WHERE ID = :id");
              $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
              $stmt->execute();
              $row = $stmt->fetch();
              return $row["UUID"];
            }
            ?>
        </tr>
      </table>
    </div>
    <div class="flex item-1">
      <h1>Alle Entbannungsanträge</h1>
      <table>
        <tr>
          <th>Spieler</th>
          <th>Datum</th>
          <th>Entscheidung</th>
          <th>Aktionen</th>
        </tr>
        <tr>
          <?php

            $stmt = MySQLWrapper()->prepare("SELECT * FROM unbans ORDER BY id DESC");
            $stmt->execute();
            if($status == 1){
              $uuid = getUUIDFromRequest($_GET["id"]);
              $stmt = MySQLWrapper()->prepare("UPDATE bans SET BANNED = 0 WHERE UUID = :uuid");
              $stmt->bindParam(":uuid", $uuid, PDO::PARAM_STR);
              $stmt->execute();
            } else if($status == 2){
              //Verkürzen auf 3 Tage
              $uuid = getUUIDFromRequest($_GET["id"]);
              $time = 259200 * 1000;
              $javatime = round(time() * 1000) + round($time);
              $stmt = MySQLWrapper()->prepare("UPDATE bans SET END = :end WHERE UUID = :uuid");
              $stmt->bindParam(":end", $javatime, PDO::PARAM_STR);
              $stmt->bindParam(":uuid", $uuid, PDO::PARAM_STR);
              $stmt->execute();
            }
            ?>
            <meta http-equiv="refresh" content="0; URL=unbans.php">
            <?php
          }
          ?>
          <div class="flex-container animated fadeIn">
            <div class="flex item-1 sidebox">
              <h1>Entbannungsantrag anschauen</h1>
              <?php
              require("./mysql.php");
              $stmt = MySQLWrapper()->prepare("SELECT * FROM unbans WHERE ID = :id");
              $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
              $stmt->execute();
              while($row = $stmt->fetch()){
                ?>
                <h5>Spieler</h5>
                <p><?php echo UUIDResolve($row["UUID"]); ?></p>
                <h5>Glaubst du der Ban war gerechtfertigt?</h5>
                <p><?php
                if($row["FAIR"] == 1){
                  ?>
                  Ja, aber ich sehe meinen Fehler ein
                  <?php
                } if($row["FAIR"] == 0){
                  ?>
                  Nein, ich habe nichts getan
                  <?php
                }
                 ?></p>
                 <h5>Nachricht</h5>
                 <p><?php echo htmlspecialchars($row["MESSAGE"]); ?></p>
                 <h5>Entbannungsantrag erstellt am</h5>
                 <p><?php echo date('d.m.Y H:i',$row["DATE"]); ?></p>
                 <?php
                 if($row["STATUS"] == 0){
                   ?>
                   <form action="unbans.php?id=<?php echo $_GET["id"]; ?>" method="post">
                     <select name="choose">
                       <option value="1">Akzeptieren und Ban aufheben</option>
                       <option value="2">Akzeptieren und Ban verkürzen</option>
                       <option value="3">Ablehnen</option>
                     </select>
                     <button type="submit" name="submit">Speichern</button>
                   </form>
                   <?php
                 } else {
                   ?>
                   <h5>Entscheidung</h5>
                   <?php
                   if($row["STATUS"] == 1){
                    echo "Ban aufgehoben";
                   } else if($row["STATUS"] == 2){
                    echo "Ban verkürzt";
                   } else if($row["STATUS"] == 3){
                    echo "Abgelehnt";
                   }
                 }
                  ?>
            Ja, aber ich sehe meinen Fehler ein
          <?php
              }
              if ($row["FAIR"] == 0) {
                ?>
            Nein, ich habe nichts getan
          <?php
              }
              ?></p>
        <h5>Nachricht</h5>
        <p><?php echo htmlspecialchars($row["MESSAGE"]); ?></p>
        <h5>Entbannungsantrag erstellt am</h5>
        <p><?php echo date('d.m.Y H:i', $row["DATE"]); ?></p>
        <?php
            if ($row["STATUS"] == 0) {
              ?>
          <form action="unbans.php?id=<?php echo $_GET["id"]; ?>" method="post">
            <select name="choose">
              <option value="1">Akzeptieren und Ban aufheben</option>
              <option value="2">Akzeptieren und Ban verkürzen</option>
              <option value="3">Ablehnen</option>
            </select>
            <button type="submit" name="submit">Speichern</button>
          </form>
        <?php
            }
        }
        ?>
    </div>
  </div>
</div>
</div>
</body>

</html>