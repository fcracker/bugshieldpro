<?php
	function createCSV($csv_name, $download) {
        $i = 1;
        $csv = "";

        /* erase the old file, if it exists */
        @unlink("../../csv/" . $csv_name . ".csv");

        /* array is in a session variable 
         * this may be useful to avoid many db queries if it is the case */
        $my_array = $_SESSION['my_array'];

        /* how many fields has the given array */
        $fields = count(array_keys($my_array[0]));

        /* extracting the titles from the array */
        foreach(array_keys($my_array[0]) as $title)
        {
            /* array_keys percurs the title of each vector */
            $csv .= $title;

            /* while it is not the last field put a semi-colon ; */
            if($i < $fields)
                $csv .= ";";

            $i++;
        }

        /* insert an empty line to better visualize the csv */
        $csv .= chr(10).chr(13);
        $csv .= chr(10).chr(13);

        /* get the values from the extracted keys */
        foreach (array_keys($my_array) as $tipo)
        {

            $i = 1;

            foreach(array_keys($my_array[$tipo]) as $sub)
            {

                $csv .= $my_array[$tipo][$sub];

                if ($i < $fields)
                    $csv .= ";";

                $i++;
            }

            $csv .= chr(10).chr(13);

        }

        /* export the csv */
        $export_csv=fopen("../../csv/". $csv_name .".csv", "w+");
        fwrite($export_csv, $csv);
        fclose($export_csv);

        /* download the csv */
        if ($download == true)
            header('Location:' . "../../csv/" . $csv_name . ".csv");

        exit();

    }
?>