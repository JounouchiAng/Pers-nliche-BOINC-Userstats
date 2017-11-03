<?php
	include "./settings/settings.php";
	date_default_timezone_set('UTC');
	//-----------------------------------------------------------------------------------
	// ab hier bitte keine Aenderungen vornehmen, wenn man nicht weiß, was man tut!!! :D
	//-----------------------------------------------------------------------------------
	
	# Beginn fuer Datenzusammenstellung User
	$result_user = mysqli_query($db_conn, "SELECT * FROM boinc_user"); //alle Userdaten einlesen
	if ( !$result_user || mysqli_num_rows($result_user) === 0 ) { 	
		$connErrorTitle = "Datenbankfehler";
		$connErrorDescription = "Es wurden keine Werte zurückgegeben.</br>
			Es bestehen wohl Probleme mit der Datenbankanbindung.";
		include "./errordocs/db_initial_err.php";
		exit();
	}
	while ($row = mysqli_fetch_assoc($result_user)) {
		$boinc_username = $row["boinc_name"];
		$boinc_wcgname = $row["wcg_name"];
		$boinc_teamname = $row["team_name"];
		$cpid = $row["cpid"];
		$datum_start = $row["lastupdate_start"];
		$datum = $row["lastupdate"];
	}
	$lastupdate_start = date("d.m.Y H:i:s",$datum_start);
	$lastupdate = date("H:i:s",$datum);
	
	$pending_credits = "0";
	
?>


<?php
//Sprache feststellen
if (isset($_GET["lang"])) $lang = $_GET["lang"];
else $lang = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));

//Sprachpaket HP einlesen
if (file_exists("./lang/" . $lang . ".txt.php")) include "./lang/" . $lang . ".txt.php";
else include "./lang/en.txt.php";

//Sprachpaket Highcharts einlesen
if (file_exists("./lang/highstock_" . $lang . ".js")) include "./lang/highstock_" . $lang . ".js";
else include "./lang/highstock_en.js";
?>

<?php include("./header.php"); ?>

	<div class="container text-center flex1">
		<div class="container">
			<h2 class="title-uppercase text-center">Pending Credits</h2>
			<div class="container">
				<?php echo $tr_hp_pendings_02; ?>	
			</div>
			<table id="table_pendings" class="table table-striped table-hover" width="100%">
				<thead>
					<th><?php echo $tr_tb_pr ?></th>
					<th><?php echo $tr_tb_pe ?></th>
				</thead>
				<tbody>									
					<?php
						$query = mysqli_query($db_conn, "SELECT * FROM boinc_grundwerte WHERE project_status = 1;"); //nur bei aktiven Projekten Werte lesen
						if ( !$query ) { 	
							$connErrorTitle = "Datenbankfehler";
							$connErrorDescription = "Es wurden keine Werte zurückgegeben.</br>
								Es bestehen wohl Probleme mit der Datenbankanbindung.";
							include "./errordocs/db_initial_err.php";
							exit();
						} elseif ( mysqli_num_rows($query) === 0 ) { 
							$connErrorTitle = "Datenbankfehler";
							$connErrorDescription = "Es existiern keine Projekte in deiner Datenbank, welche als aktiv (1) gesetzt sind.";
							include "./errordocs/db_initial_err.php";
							exit();
						}
						$ctx = stream_context_create(array(
								'http' => array(
								'timeout' => 1
								)
							)
						);
						$xml_string_pendings = "false";
						$pendings_gesamt = 0;
						while ($row = mysqli_fetch_assoc($query)) {
							$xml_string_pendings = @file_get_contents($row['url'] . "pending.php?format=xml&authenticator=" . $row['authenticator'], 0, $ctx);
							if ($xml_string_pendings == FALSE) {
								$projectname = $row['project'];
								$pending_credits = $row['pending_credits'];
								} else {
								$projectname = $row['project'];
								$xml_pendings = @simplexml_load_string($xml_string_pendings);
								$pending_credits = intval($xml_pendings->total_claimed_credit);
							}
							$pendings_gesamt = $pendings_gesamt + $pending_credits;
							$sql_pendings = "UPDATE boinc_grundwerte SET pending_credits='" . $pending_credits . "' WHERE project_shortname='" . $row['project_shortname'] . "'"; //aktuelle Pendings des Projektes in Grundwerttabelle eintragen
							mysqli_query($db_conn, $sql_pendings); //Werte in DB eintragen
							
							echo "<tr><td>" . $projectname . "</td>";
							echo "<td>" . number_format($pending_credits, 0, $dec_point, $thousands_sep) . "</td></tr>";
						}
						echo "<thead><tr><tr><td>GESAMT Pendings</td>";
						echo "<td>" . number_format($pendings_gesamt, 0, $dec_point, $thousands_sep) . "</td></tr></thead>";
					?>
				</tbody>
			</table>
		</div>
	</div>

	<script>
		$(document).ready(function() {
			$('#table_pendings').DataTable( {
				"language": {
					"decimal": "<?php echo $dec_point; ?>",
					"thousands": "<?php echo $thousands_sep; ?>",
					"search":	"<?php echo $search; ?>"
				},
				"columnDefs": [ {
					"targets": 'no-sort',
					"orderable": false,
				}],
				"paging": false,
				"info": false
			} );
		} );
	</script>
		
<?php include("./footer.php"); ?>