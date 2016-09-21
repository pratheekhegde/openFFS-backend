<?php
require_once '../CoreLib.php';
// Include the main TCPDF library (search for installation path).
require_once('tcpdf/tcpdf.php');

session_start();
date_default_timezone_set('Asia/Kolkata');
$Timestamp=date('d-m-Y h:i:s A');

//Main Logic Starts here
if(($_SESSION['LoginStatus']==0)||($_SESSION['LoginStatus']==NULL)){
header("location: index.php");
exit();
}

if(!($_POST['FacID'] || $_POST['Class'])){
header("location: ../error.php");
exit();
}
$FacID=strtolower($_POST['FacID']);
$FacName=GetFacName($_POST['FacID']);
$Class=$_POST['Class'];
$ClassName=GetClassName($Class);

//******************************************************************Generation of PDF Starts Here*******************************************************
//******************************************************************************************************************************************************
					$BUILDInfo=GetBUILD();
					//set the name of the file generated;
					$Filename=$FacName."-".$ClassName." (Feedback Report).pdf";
					//$Filename=rawurlencode($Filename);
					
					//check for the feedbacks in the database if none found return back
					$bd = mysql_connect($mysql_hostname, $mysql_user, $mysql_password) or die("Could not connect database");
					mysql_select_db($mysql_database, $bd) or die("Could not select database");
					$qry="SELECT * FROM $FacID WHERE Class='".$Class."'";
					$result=mysql_query($qry);
					$num=mysql_num_rows($result);
					if($num==0){
						//echo "No reports";
						header("location: LoadFac.php");
						exit();
					}
					
					//get the comments and write to the pdf
					
					// Get all fields names in table from the database.
					$fields = mysql_list_fields($mysql_database,$FacID);
					
					// Count the table fields and put the value into $columns.
					$columns = mysql_num_fields($fields);
					$numstud=0;//Number of entries
					
					//Get the comments of the faculty and class selected from the database and store it in a variable
					while ($l = mysql_fetch_array($result)) {
						$numstud++;
						$commentsHTML .="<tr><td><small><font color=\"blue\">".$numstud.")</font> ".$l['Q21']."</small></td></tr>";
					}
					//echo $commentsHTML;
					
					//Calculate the average marks and store it in a variable
					$TotalMarks=0;
					for($i=1;$i<=20;$i++){
						$c="Q".$i;
						$result=mysql_query("SELECT SUM($c) AS colsum FROM $FacID WHERE Class='".$Class."'");//finding the column total
						while($row=mysql_fetch_assoc($result)){
							$TotalMarks+=$row['colsum'];//adding the column total to get total marks
						}	
					}	
					$Totalpercent=$TotalMarks/$numstud;
					$Totalpercent=round($Totalpercent,2);
					
					
					// create new PDF document
					$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

					// set document information
					$pdf->SetCreator(FFS_PDF_CREATOR);
					$pdf->SetAuthor('FFSv2.160814');
					$pdf->SetTitle($FacName."-".$ClassName."- Feedback Report");
					$pdf->SetSubject('Feedback Report');

										
					// set default monospaced font
					$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
					
									
					// remove default header/footer
					$pdf->setPrintHeader(false);
					//$pdf->setPrintFooter(false);
					
					// set margins
					$pdf->SetFooterMargin(8);


					// set auto page breaks
					$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

					// set image scale factor
					$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

					// set some language-dependent strings (optional)
					if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
						require_once(dirname(__FILE__).'/lang/eng.php');
						$pdf->setLanguageArray($l);
					}

					// ---------------------------------------------------------

					// set default font subsetting mode
					$pdf->setFontSubsetting(true);

					// Set font
					// dejavusans is a UTF-8 Unicode font, if you only need to
					// print standard ASCII chars, you can use core fonts like
					// helvetica or times to reduce file size.
					$pdf->SetFont('dejavusans', '', 14, '', true);

					// Add a page
					// This method has several options, check the source code documentation for more information.
					$pdf->AddPage();
				
				
					// to print
					$html = <<<EOD

					<div style="text-align:center"><img src="../assets/coll_header.jpg" alt="SMVITM" width="760" height="95" border="0" /><br />
					<u><b>Faculty Feedback Report</b></u></div><br>
					<font size="10"><table border="0">
									<tr><td align="left">
										<b>Faculty Name</b>: $FacName<br>
										<b>Class</b>: $ClassName<br>
										<b>Report Type</b>: Complete
										</td>
										<td align="right"><font color="green"><b>Generated On</b>: $Timestamp<br>
														<b>Generated By</b>:</font>$BUILDInfo
										</td>
									</tr>
									<tr><td colspan="2"></td></tr>
									<tr><td colspan="2"><table border="1">
															<tr><td align="left"><b>Total Percentage:</b> $Totalpercent%<br><b>Total Students:</b> $numstud</td></tr></table></td>
									</tr>
									<tr><td colspan="2"></td></tr>
									<tr><td colspan="2" align="center"><font size="12" color="#00008b">Comments From The Students</font></td></tr>
									</table></font>
					<table border="1">
					$commentsHTML
					</table>
					<br><br><br><br><br>
					<table border="0">
					<tr><td align="right">_________________</td></tr>
					<tr height="30"><td align="right"><small>Principal/Dean Signature</small></td></tr>
					</table>
					
EOD;

					// output the HTML content
					$pdf->writeHTML($html, true, false, true, false, '');


					// ---------------------------------------------------------

					// Close and output PDF document
					// This method has several options, check the source code documentation for more information.
					$pdf->Output($Filename, 'D');
				
	
	
exit;    
?>