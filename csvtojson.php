<?php

require_once ('Csv/Exception.php');
require_once ('Csv/Exception/FileNotFound.php');
require_once ('Csv/Reader/Abstract.php');
require_once ('Csv/Reader.php');
require_once ('Csv/Reader/String.php');
require_once ('Csv/AutoDetect.php');
require_once ('Csv/Dialect.php');

//--------------------------------------------------------------------------------------------------
/**
 * @brief Format JSON nicely
 *
 * From umbrae at gmail dot com posted 10-Jan-2008 06:21 to http://uk3.php.net/json_encode
 *
 * @param json Original JSON
 *
 * @result Formatted JSON
 */
function json_format($json)
{
    $tab = "  ";
    $new_json = "";
    $indent_level = 0;
    $in_string = false;

/*    $json_obj = json_decode($json);

    if($json_obj === false)
        return false;

    $json = json_encode($json_obj); */
    $len = strlen($json);

    for($c = 0; $c < $len; $c++)
    {
        $char = $json[$c];
        switch($char)
        {
            case '{':
            case '[':
                if(!$in_string)
                {
                    $new_json .= $char . "\n" . str_repeat($tab, $indent_level+1);
                    $indent_level++;
                }
                else
                {
                    $new_json .= $char;
                }
                break;
            case '}':
            case ']':
                if(!$in_string)
                {
                    $indent_level--;
                    $new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
                }
                else
                {
                    $new_json .= $char;
                }
                break;
            case ',':
                if(!$in_string)
                {
                    $new_json .= ",\n" . str_repeat($tab, $indent_level);
                }
                else
                {
                    $new_json .= $char;
                }
                break;
            case ':':
                if(!$in_string)
                {
                    $new_json .= ": ";
                }
                else
                {
                    $new_json .= $char;
                }
                break;
            case '"':
                if($c > 0 && $json[$c-1] != '\\')
                {
                    $in_string = !$in_string;
                }
            default:
                $new_json .= $char;
                break;                    
        }
    }

    return $new_json;
}

$keys = array(
'GBIF_ID',
'ALA_ID',
'MoA_note',
'MoA_Name',
'ALA_Scientific_Name',
'ALA_Matched_Scientific_Name',
'ALA_Name_not_in_national_checklists',
'GBIF_Scientific_name',
'Name_issue',
'ALA_treatment',
'GBIF_Locality',
'ALA_Locality',
'MoA_Locality',
'ALA_Latitude_processed',
'ALA_Longitude_processed',
'ALA_Coordinate_Precision',
'ALA_Coordinate_Uncertainty_in_Metres_parsed',
'ALA_Location_Quality',
'GBIF_Latitude',
'GBIF_Longitude',
'GBIF_Coordinate_precision',
'Aggr_rounded_LatDD',
'Aggr_rounded_LonDD',
'MoA_Lat_DD_WGS84',
'MoA_Long_DD_WGS84',
'Euc_D',
'MoA_Unc',
'MoA_Unc_km',
'Offset',
'Spatial_problem',
'Spatial_problem_resolution',
'ALA_Year_parsed',
'ALA_Month_parsed',
'ALA_Day_parsed',
'ALA_Event_Date_parsed',
'GBIF_Date_collected',
'MoA_Day',
'Agg_Day',
'MoA_Month',
'Agg_Month',
'MoA_Year',
'Agg_Year',
'Date_problem'
);

$filename = '5111-SP-1-editor/Working_files/Comparison_table.csv';

$reader = new Csv_Reader($filename);

$count = 0;

$geo = new stdclass;
$geo->type = "GeometryCollection";
$geo->geometries = array();

foreach ($reader as $row) 
{
	if ($count != 0)
	{
		$values = array();

		foreach ($keys as $k => $v)
		{
			$values[$v] = $row[$k];
		}
		
		if ($values['Offset'] > 0)
		{
			if (($values['GBIF_Longitude'] != 0) && ($values['MoA_Long_DD_WGS84'] != 0))
			{
				// GBIF point
				$gbif = new stdclass;
				$gbif->type = 'Point';
				$gbif->coordinates = array();
				$gbif->coordinates[] = (float)$values['GBIF_Longitude'];
				$gbif->coordinates[] = (float)$values['GBIF_Latitude'];
				
				//$geo->geometries[] = $gbif;
				
				// MoA point
				$moa = new stdclass;
				$moa->type = 'Point';
				$moa->coordinates = array();
				$moa->coordinates[] = (float)$values['MoA_Long_DD_WGS84'];
				$moa->coordinates[] = (float)$values['MoA_Lat_DD_WGS84'];
				
				//$geo->geometries[] = $moa;
				
				// MoA uncertainty
				$moa_uncertainty = new stdclass;
				$moa_uncertainty->type = 'Circle';
				$moa_uncertainty->coordinates = $moa->coordinates;
				$moa_uncertainty->radius = (float)$values['MoA_Unc_km'] * 1000;
				
				$geo->geometries[] = $moa_uncertainty;
				
				// Connect GBIF and MoA record
				$line = new stdclass;
				$line->type = 'LineString';
				$line->coordinates = array();
				$line->coordinates[] = $moa->coordinates;
				$line->coordinates[] = $gbif->coordinates;
	
				$geo->geometries[] = $line;
				
				/*
				if (count($geo->geometries) > 10)
				{
					echo json_encode($geo);
					echo "\n";
					exit();
				}
				*/
				
	
				/*			
				echo 'GBIF=' . $values['GBIF_Latitude'] . ' ' . $values['GBIF_Longitude'] . "\n";
				echo 'MoA=' . $values['MoA_Lat_DD_WGS84'] . ' ' . $values['MoA_Long_DD_WGS84'] . "\n";
				echo 'MoA_Unc_km=' . $values['MoA_Unc_km'] .  "\n";
				*/
			}
		}
		
	}
	
	$count++;
	
	
}   

echo json_format(json_encode($geo));

?>