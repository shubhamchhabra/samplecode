<?php
/**
 * Created by PhpStorm.
 * User: shubham
 * Date: 12/23/2017
 * Time: 3:24 PM
 */

namespace api\modules\v1\controllers;

use backend\modules\attendance\models\AttAttendance;
use backend\modules\attendance\models\AttLeave;
use backend\modules\attendance\models\AttCycle;
use backend\modules\attendance\models\AttCycleStatus;
use backend\modules\attendance\models\AttCycleUser;
use backend\modules\attendance\models\AttLeaveDate;
use Yii;
use yii\filters\Cors;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\rest\ActiveController;
use common\models\FullCalendarEvent;

class AttendanceController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\AttendanceLeave';
    CONST RETURN_SUCCESS = "success";
    CONST RETURN_ERROR = "error";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);
        unset($actions['view']);
        unset($actions['index']);
        return $actions;
    }

    public function behaviors()
    {

        return ArrayHelper::merge(parent::behaviors(), [
            'corsFilter' => [
                'class' => Cors::className(),
            ],
        ]);
    }

    public function actionGetCycle($user_id)
    {
        if (!empty($user_id)) {
            try {
                $cyclesArray = AttCycle::userCyclesArray($user_id);
                $finalArray = [];
                foreach($cyclesArray as $cycleArray) {
                    if (!$cycleArray['is_auto_applyable']) {
                        $finalArray[] = $cycleArray;
                    }
                }
                if (!empty($finalArray)) {
                    return $response = [
                        'result' => 1,
                        'message' => self::RETURN_SUCCESS,
                        'details' => $finalArray
                    ];
                } else {
                    return $response = [
                        'status' => self::RETURN_ERROR,
                        'message' => 'No data found'
                    ];
                }
            } catch (Exception $ex) {
                return $response = [
                    'status' => self::RETURN_ERROR,
                    'message' => 'Internal server error'
                ];
            }

        } else {
            return $response = [
                'status' => self::RETURN_ERROR,
                'message' => 'No User Exists'
            ];
        }
    }

    public function actionGetCycleStatuses($user_id){
        $attCycleUserModel = AttCycleUser::find()->where(['user_id' => $user_id])->all();

        if (!empty($user_id)) {
            try {
                $dataArray = array();
                $userCycleCount = count($attCycleUserModel);
                if ($userCycleCount > 0) {
                    foreach ($attCycleUserModel as $cycleUserModel) {
                        $cycleId = $cycleUserModel->cycle_id;
                        $cycleStatusModels = AttCycleStatus::find()->where(['cycle_id' => $cycleId])->all();
                        foreach ($cycleStatusModels as $cycleStatusModel) {
                            $cycleStatusModel=array(
                                'cycle_status_id'=>$cycleStatusModel->id,
                                'leave_color'=>$cycleStatusModel->color,
                                'leave_value'=>$cycleStatusModel->value,
                                'leave_type'=>$cycleStatusModel->name,
                            );
                            array_push($dataArray, $cycleStatusModel);
                        }
                    }

                    if (!empty($dataArray)) {
                        return $response = [
                            'result'=>1,
                            'message' => self::RETURN_SUCCESS,
                            'details' => $dataArray
                        ];
                    } else {
                        return $response = [
                            'status' => self::RETURN_ERROR,
                            'message' => 'Not Found'
                        ];
                    }
                } else {
                    return $response = [
                        'status' => self::RETURN_ERROR,
                        'message' => 'No such data found'
                    ];
                }
            } catch (Exception $ex) {
                return $response = [
                    'status' => self::RETURN_ERROR,
                    'message' => 'Internal server error'
                ];
            }

        } else {
            return $response = [
                'status' => self::RETURN_ERROR,
                'message' => 'No User Exists'
            ];
        }
    }

    public function actionSaveLeaveRequest(){
        /* Json data fetch and decode */
        $dataPOST = json_decode(trim(file_get_contents('php://input')), true);
        if (!empty($dataPOST)) {

            //Validations start
            if (!isset($dataPOST['user_id']) || empty($dataPOST['user_id'])) {
                return $response = [
                    'status' => self::RETURN_ERROR,
                    'message' => "user_id is required"
                ];
            } else if (!isset($dataPOST['reason']) || empty($dataPOST['reason'])) {
                return $response = [
                    'status' => self::RETURN_ERROR,
                    'message' => "reason is required"
                ];
            }
            //Validations end

            $userId = $dataPOST['user_id'];
            $reason = $dataPOST['reason'];

            if (!empty($dataPOST['statuskey_dates'])) {

                $leaveEventsArray = [];

                foreach ($dataPOST['statuskey_dates'] as $key => $val) {
                    foreach ($val as $v) {
                        if(!empty($key) && !empty($v))
                            $leaveEventsArray[] = [
                                'cycleStatusId' => $key,
                                'start' => date('Y-m-d', strtotime($v))
                            ];
                    }
                }

                return AttLeave::saveLeaveRequest($userId, $leaveEventsArray, $reason);
            }
        }

        return $response = [
            'status' => self::RETURN_ERROR,
            'message' => "No/Incorrect JSON sent"
        ];
    }

    public function actionCreateOld()
    {
        /* Json data fetch and decode */
        $dataPOST = json_decode(trim(file_get_contents('php://input')), true);
        if (!empty($dataPOST)) {

            //Validations start
            if (!isset($dataPOST['user_id']) || empty($dataPOST['user_id'])) {
                return $response = [
                    'status' => self::RETURN_ERROR,
                    'message' => "user_id is required"
                ];
            } else if (!isset($dataPOST['reason']) || empty($dataPOST['reason'])) {
                return $response = [
                    'status' => self::RETURN_ERROR,
                    'message' => "reason is required"
                ];
            }
            //Validations end

            $userId = $dataPOST['user_id'];
            $reason = $dataPOST['reason'];

            if (!empty($dataPOST['statuskey_dates'])) {
                $leaveData = $dataPOST['statuskey_dates'];

                $leaveModel = new AttLeave();
                $leaveModel->reason = $reason;
                $leaveModel->user_id = $userId;

                if(!$leaveModel->save()) {
                    return $response = [
                        'status' => self::RETURN_ERROR,
                        'message' => "Validation error in saving leave"
                    ];
                }

                foreach ($leaveData as $key => $val) {
                    foreach ($val as $v) {
//                      $leaveId = $key + 1;
                        $leaveDateModel = new AttLeaveDate();
                        $leaveDateModel->date = date('Y-m-d', strtotime($v));
                        $leaveDateModel->cycle_status_id = $key;
                        $leaveDateModel->leave_id = $leaveModel->id;

                        if(!$leaveDateModel->save()) {
                            return $response = [
                                'status' => self::RETURN_ERROR,
                                'message' => "validation error in saving leave Date",
                            ];
                        }

                    }
                }

                return $response = [
                    'status' => self::RETURN_SUCCESS,
                    'message' => "data added successfully"
                ];

            } else {
                return $response = [
                    'status' => self::RETURN_ERROR,
                    'message' => "statuskey_dates empty"
                ];
            }
        }

        return $response = [
            'status' => self::RETURN_ERROR,
            'message' => "No/Incorrect JSON sent"
        ];
    }

    public function actionView($id)
    {
        $model = AttAttendance::find()->one();
        if ($model) {
            $response['status'] = self::RETURN_SUCCESS;
            $response['data'] = $model->toArray();
        } else {
            $response['status'] = self::RETURN_ERROR;
            $response['message'] = 'Data not found';
        }
        return $response;
    }

    public function actionGetAttendance($user_id){

        if (empty($user_id)) {
            $response['status'] = self::RETURN_ERROR;
            $response['message'] = 'user_id required';
            return $response;
        }

        $attendanceModels = AttAttendance::find()->where(array('user_id'=>$user_id))->all();

        $events = array();

        foreach ($attendanceModels as $attendanceModel) {
            $event = new FullCalendarEvent();
            $event->isNew = false;
            $event->id = $attendanceModel->id;
            $event->cycleStatusId = $attendanceModel->cycleStatus->id;
            $event->dayLength = $attendanceModel->cycleStatus->day_length;
            $event->start = date('Y-m-d', strtotime($attendanceModel->for_date));
            $event->backgroundColor = $attendanceModel->cycleStatus->color;
            $events[] = $event;

            $events[] = [
                'date' => date('Y-m-d', strtotime($attendanceModel->for_date)),
                'cycle_status_id' => $attendanceModel->cycleStatus->id,
                'day_length' => $attendanceModel->cycleStatus->day_length,
                'background_color' => $attendanceModel->cycleStatus->color,
            ];
        }

        $response['status'] = self::RETURN_SUCCESS;
        $response['message'] = 'Attendance returned successfully';
        $response['details'] = $events;
        return $response;
    }
}
