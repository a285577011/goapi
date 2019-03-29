<?php

namespace app\modules\experts\models;

use Yii;

/**
 * This is the model class for table "articles_periods".
 *
 * @property integer $articles_periods_id
 * @property integer $articles_id
 * @property string $periods
 * @property string $lottery_code
 * @property string $schedule_code
 * @property integer $league_id
 * @property string $league_short_name
 * @property string $home_short_name
 * @property string $visit_short_name
 * @property string $home_team_rank
 * @property string $visit_team_rank
 * @property string $home_team_img
 * @property string $visit_team_img
 * @property string $rq_nums
 * @property string $fen_cutoff
 * @property string $start_time
 * @property string $endsale_time
 * @property string $pre_result
 * @property string $pre_odds
 * @property integer $featured
 * @property integer $status
 * @property integer $deal_status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class ArticlesPeriods extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'articles_periods';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['articles_id', 'periods', 'lottery_code', 'pre_result'], 'required'],
            [['articles_id', 'league_id', 'featured', 'status', 'deal_status'], 'integer'],
            [['fen_cutoff'], 'number'],
            [['start_time', 'endsale_time', 'create_time', 'modify_time', 'update_time'], 'safe'],
            [['periods', 'lottery_code', 'schedule_code', 'league_short_name', 'home_short_name', 'visit_short_name', 'home_team_img', 'visit_team_img', 'pre_result'], 'string', 'max' => 100],
            [['home_team_rank', 'visit_team_rank', 'rq_nums'], 'string', 'max' => 25],
            [['pre_odds'], 'string', 'max' => 11],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'articles_periods_id' => 'Articles Periods ID',
            'articles_id' => 'Articles ID',
            'periods' => 'Periods',
            'lottery_code' => 'Lottery Code',
            'schedule_code' => 'Schedule Code',
            'league_id' => 'League ID',
            'league_short_name' => 'League Short Name',
            'home_short_name' => 'Home Short Name',
            'visit_short_name' => 'Visit Short Name',
            'home_team_rank' => 'Home Team Rank',
            'visit_team_rank' => 'Visit Team Rank',
            'home_team_img' => 'Home Team Img',
            'visit_team_img' => 'Visit Team Img',
            'rq_nums' => 'Rq Nums',
            'fen_cutoff' => 'Fen Cutoff',
            'start_time' => 'Start Time',
            'endsale_time' => 'Endsale Time',
            'pre_result' => 'Pre Result',
            'pre_odds' => 'Pre Odds',
            'featured' => 'Featured',
            'status' => 'Status',
            'deal_status' => 'Deal Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
