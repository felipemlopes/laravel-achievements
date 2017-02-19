<?php

namespace Gstt\Achievements;

use Gstt\Achievements\Model\AchievementDetails;
use Gstt\Achievements\Model\AchievementProgress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

abstract class Achievement
{

    /**
     * The unique identifier for the achievement.
     *
     * @var string
     */
    public $id;

    /*
     * The achievement name
     */
    public $name = "Achievement";

    /*
     * A small description for the achievement
     */
    public $description = "";

    /**
     * The amount of points required to unlock this achievement.
     */
    public $points = 1;

    /*
     * Whether this is a secret achievment or not.
     */
    public $secret = false;

    /**
     * Achievement constructor.
     * Should add the achievement to the database.
     */
    public function __construct()
    {
        $this->getModel();
    }

    /**
     * Gets the full class name.
     *
     * @return string
     */
    public function getClassName()
    {
        return self::class;
    }

    /**
     * Gets the details class for this achievement.
     *
     * @return AchievementDetails
     */
    public function getModel()
    {
        $model = AchievementDetails::where('class_name', self::class)->first();
        if (is_null($model)) {
            $model = new AchievementDetails();
            $model->class_name = self::class;
        }

        // Updates the model with data from the achievement class
        $model->name        = $this->name;
        $model->description = $this->description;
        $model->points      = $this->points;
        $model->secret      = $this->secret;

        // Saves
        $model->save();

        return $model;
    }

    /**
     * Adds a specified amount of points to the achievement.
     *
     * @param mixed $achiever The entity that will add progress to this achievement
     * @param int   $points   The amount of points to be added to this achievement
     */
    public function addProgressToAchiever($achiever, $points)
    {
        $progress = $this->getOrCreateProgressForAchiever($achiever);
        $progress->points = $progress->points + $points;
        $progress->save();
    }

    /**
     * Sets a specified amount of points to the achievement.
     *
     * @param mixed $achiever The entity that will add progress to this achievement
     * @param int   $points   The amount of points to be added to this achievement
     */
    public function setProgressToAchiever($achiever, $points)
    {
        $progress = $this->getOrCreateProgressForAchiever($achiever);
        $progress->points = $points;
        $progress->save();
    }

    /**
     * Gets the achiever's progress data for this achievement, or creates a new one if not existant
     * @param mixed|null $achiever
     *
     * @return AchievementProgress
     */
    public function getOrCreateProgressForAchiever($achiever)
    {
        $className = self::class;

        $progress = $achiever->achievements()->whereHas(
            'details',
            function (Builder $query) use ($className) {
                $query->where('class_name', $className);
            }
        );

        if (is_null($progress)) {
            $progress = new AchievementProgress();
            $progress->details()->associate($this->getModel());
            $progress->achiever()->associate($achiever);

            $progress->save();
        }

        return $progress;
    }

    /**
     * Will be called when the achievement is unlocked.
     * @param $progress
     */
    public function whenUnlocked($progress)
    {
    }
}