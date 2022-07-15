<?php

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

use App\Models\course_code;
use App\Models\Assignstaff;
use App\Models\Studentmark;
use App\Models\Department;
use App\Models\program;
use App\Models\school;
use App\Models\Peopo;
use App\Models\Peo;
use App\Models\Po;

class AttainmentController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware(['auth', 'hod']);
    }

    public function getPrograms()
    {
        $department = Department::find(auth()->user()->department_id);
        $programs = Studentmark::where('department_name', $department['department_name'])->select('program_name')->get();
        $disctinctPrograms = $programs->unique('program_name');

        return response()->json($disctinctPrograms);
    }

    public function getCourses($program_name)
    {
        $department = Department::find(auth()->user()->department_id);
        $coConsolidated = array();
        $courses = DB::select('SELECT consolidated_co FROM studentmarks WHERE (program_name = :program) AND (department_name = :department) ORDER BY consolidated_co DESC', ['program' => $program_name, 'department' => $department['department_name']]);
        for ($i = 0; $i < sizeof($courses); $i++) {
            $EACHcourse = $courses[$i];
            foreach ($EACHcourse as $keys => $values) {
                $COJsonKey =  $values;
            }
            $CO = json_decode($COJsonKey, true);
            array_push($coConsolidated, $CO);
        }
        $coConsolidated = collect($coConsolidated)->filter();

        return response()->json($coConsolidated);
    }

    public function getUserSchool()
    {
        $department = Department::find(auth()->user()->department_id);
        $school = school::find($department->school_id);
        return response()->json($school);
    }

    public function addPeoPoMapping(Request $request)
    {
      $status = "";
      $message = "";
      $request->validate([
        'school_id' => ['required'],
        'peopos' => ['required'],
      ]);

      try {
        Peopo::create([
              'id' => Str::uuid(),
              'school_id' => $request->school_id,
              'mapping' => json_encode($request->peopos),
              'saved_by' => auth()->user()->id,
        ]);
        $status = 'success';
        $message = "PEO-PO Mapping Added Successfully";
      } catch (Exception $e) {
        Log::warning('Error Adding PEO-PO Mapping',$e->getMessage());
        $status = 'error';
        $message = "Unable to Add PEO-PO Mapping";
      }
      return response()->json(['status' => $status, 'message' => $message]);
    }

    public function getPeoPoMapping(Request $request, $school_id)
    {
      $popeoMapppingChart = array();
      $MapKey = array();
      $MapKeyValues = array();
      // $NoOfPeos = Peo::where('school_id', $school_id)->count();
      // $NoOfPos = Po::where('school_id', $school_id)->count();
      $NoOfPeos = 6;
      $NoOfPos = 7;
      $PeoPoMapping = DB::select('SELECT mapping FROM peopos WHERE school_id = :id', ['id' => $school_id]);
      $PeoPoMappingId = DB::select('SELECT id FROM peopos WHERE school_id = :id', ['id' => $school_id]);

      // justification stuff
      $MapKeys = array();
      $MapKeysValues = array();
      $JustificationKeyValues = array();
      $PeoPoMappingJustification = DB::select('SELECT mappingJustification FROM justificationpeopos WHERE school_id = :id', ['id' => $school_id]);
      $PeoPoMappingJustificationId = DB::select('SELECT id FROM justificationpeopos WHERE school_id = :id', ['id' => $school_id]);


      if ($PeoPoMapping == null) {
        $PeoPoMapping = array('');
        return response()->json(['mapping'=>$MapKeyValues, 'justication'=>$MapKeysValues, 'peoCount'=>$NoOfPeos, 'poCount'=>$NoOfPos]);
      }
      $mapping = $PeoPoMapping[0];

      foreach ($mapping as $key => $value) {
        $MapKey =  $value;
      }
      $MapKey = json_decode($MapKey, true);
      for ($i = 0; $i < sizeof($MapKey); $i++) {
        $MapKeyValues[$i] = $MapKey[$i];
      }
      $labelData = "peopo";
      $labelChart = array("PO1", "PO2", "PO3", "PO4", "PO5", "PO6", "PO7", "PO8", "PO9", "PO10", "PO11", "PO12", "PO13", "PO14", "PO15");
      // $labelChart = array("PEO1", "PEO2", "PEO3", "PEO4", "PEO5", "PEO6", "PEO7", "PEO8", "PEO9", "PEO10", "PEO11", "PEO12", "PEO13", "PEO14", "PEO15");

      $mappingChart = array();

      // correlation
      $highCorrelation = array();
      $moderateCorrelation = array();
      $lowCorrelation = array();

      $SlightCorrelation = 0;
      $ModerateCorrelation = 0;
      $HighCorrelation = 0;

      if (isset($MapKeyValues[$NoOfPeos-1])) {
        for ($i=0; $i<$NoOfPeos; $i++) {
          // array_push($mappingChart,$labelChart[$i]);
          for ($j=1; $j<=$NoOfPos; $j++) {
            $mappingChart[] = $MapKeyValues[$i][$labelData.$j];
          }
        }
        $requiredPOlabels = array();
        for ($i=0; $i < $NoOfPos; $i++) {
          $requiredPOlabels[] = $labelChart[$i];
        }
        $peopoMappingChart = array_chunk($mappingChart, $NoOfPos);

        array_unshift($peopoMappingChart, $requiredPOlabels);
        for ($i=0; $i < $NoOfPos; $i++) {
          $popeoMapppingChart[] = array_column($peopoMappingChart, $i);
        }
        $popeoMapppingChart = collect($popeoMapppingChart)->filter();


        $checkEmptyPo = 0;
        for ($i=0; $i < sizeof($mappingChart); $i++) {
            if (null == $mappingChart[$i]) {
              $checkEmptyPo = 1;
            }
        }

        if ($checkEmptyPo == 0) {
          // Counting Correlation
          $correlation = array_count_values($mappingChart);

            if (array_key_exists(1,$correlation)) {
              $SlightCorrelation = round(((($correlation[1])/($NoOfPeos*$NoOfPos))*100), 2);
            }
            if (array_key_exists(2,$correlation)) {
              $ModerateCorrelation = round(((($correlation[2])/($NoOfPeos*$NoOfPos))*100), 2);
            }
            if (array_key_exists(3,$correlation)) {
              $HighCorrelation = round(((($correlation[3])/($NoOfPeos*$NoOfPos))*100), 2);
            }
        }


        // getRoute data for justification
        $mappingPeoPoJustification = array();
        $mappingPeoPoJustification = array_chunk($mappingChart, $NoOfPos);
        $high = array();
        $moderate = array();
        $low = array();
        $row = array();

        $rowsInMapping = $NoOfPeos; // Also equals to "sizeof($mappingPeoPoJustification)"

        for ($noOfRows=0; $noOfRows < $NoOfPeos; $noOfRows++) {
          array_push($high, $row);
          array_push($moderate, $row);
          array_push($low, $row);
        }

        $peoLabel = "PEO-";
        for ($i = 0, $rowNo = 0;$i < $NoOfPeos,$rowNo < $NoOfPeos;$i++, $rowNo++) {
          for ($j=0; $j < $NoOfPos; $j++) {
            if (1 == $mappingPeoPoJustification[$i][$j]) {
              array_push($low[$rowNo], $j+1);
            }
            elseif (2 == $mappingPeoPoJustification[$i][$j]) {
              array_push($moderate[$rowNo], $j+1);
            }
            else {
              array_push($high[$rowNo], $j+1);
            }
          }
        }


        for ($i = 0;$i < $NoOfPeos;$i++) {
          array_push($highCorrelation, preg_filter('/^/', 'PO', $high[$i]));
        }
        for ($i = 0;$i < $NoOfPeos;$i++) {
          array_push($moderateCorrelation, preg_filter('/^/', 'PO', $moderate[$i]));
        }
        for ($i = 0;$i < $NoOfPeos;$i++) {
          array_push($lowCorrelation, preg_filter('/^/', 'PO', $low[$i]));
        }

        for ($i=0; $i < $NoOfPeos; $i++) {
          array_unshift($highCorrelation[$i], $peoLabel.($i+1));
          array_unshift($moderateCorrelation[$i], $peoLabel.($i+1));
          array_unshift($lowCorrelation[$i], $peoLabel.($i+1));
        }

        // array string
        for ($i=0; $i < $NoOfPeos; $i++) {
          $highCorrelation[$i] = implode(", ",$highCorrelation[$i]);

          $moderateCorrelation[$i] = implode(", ",$moderateCorrelation[$i]);

          $lowCorrelation[$i] = implode(", ",$lowCorrelation[$i]);
        }

        for ($i=0; $i < $NoOfPeos; $i++) {
          $highCorrelation[$i][5] = ":";
          $moderateCorrelation[$i][5] = ":";
          $lowCorrelation[$i][5] = ":";
        }


        // get route for justificationData

        if ($PeoPoMappingJustification == null) {
          $MapKeysValues = array();
        }
        else {
          $mappingJustification = $PeoPoMappingJustification[0];

          foreach ($mappingJustification as $key => $value) {
            $MapKeys =  $value;
          }
          $MapKeys = json_decode($MapKeys, true);
          for ($i = 0; $i < sizeof($MapKeys); $i++) {
            $MapKeysValues[$i] = $MapKeys[$i];
          }
        }
      }

      return response()->json(['peoCount'=>$NoOfPeos, 'poCount'=>$NoOfPos,'mapping'=>$MapKeyValues,'justification'=>$MapKeysValues,
      'SlightCorrelation'=>$SlightCorrelation, 'ModerateCorrelation'=>$ModerateCorrelation, 'HighCorrelation'=>$HighCorrelation,
      'justificationHigh'=>$highCorrelation, 'justificationModern'=>$moderateCorrelation, 'justificationLow'=>$lowCorrelation,
      'justificationId'=>$PeoPoMappingJustificationId, 'MappingId' => $PeoPoMappingId, 'chart'=>$popeoMapppingChart
    ]);
    }

    public function editPeoPoMapping(Request $request, $peopoMappingId)
    {
      $status="";
      $message="";
      $request->validate([
          'peopos' => 'required',
      ]);
      try {
          if($peopo = Peopo::find($peopoMappingId))
          {
              $peopo->mapping = $request->peopos;
              $peopo->save();
          }

          $status = 'success';
          $message = "PEO-PO Mapping Updated Successfully";
      } catch (Exception $e) {
          Log::warning('Error Updating PEO-PO Mapping',$e->getMessage());
          $status = 'error';
          $message = "Unable to Update PEO-PO Mapping";
      }

      return response()->json(['status' => $status,'message'=>$message]);
    }
}
