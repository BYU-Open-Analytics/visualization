<?php

use Phalcon\Mvc\Controller;

class StudentSkillsStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Student Skills Stats');
	}

	public function skillsAction($raw = false, $debug = false) {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}
		$skillsHelper = new SkillsHelper();

		$stats = [
		    'student' => [
			['id' => 'time', 'score' => $skillsHelper->calculateTimeScore($context->getUserName(), $raw, $debug)],
			['id' => 'activity', 'score' => $skillsHelper->calculateActivityScore($context->getUserName(), $raw, $debug)],
			['id' => 'consistency', 'score' => $skillsHelper->calculateConsistencyScore($context->getUserName(), $raw, $debug)],
			['id' => 'awareness', 'score' => $skillsHelper->calculateAwarenessScore($context->getUserName(), $raw, $debug)],
			['id' => 'deepLearning', 'score' => $skillsHelper->calculateDeepLearningScore($context->getUserName(), $raw, $debug)],
			['id' => 'persistence', 'score' => $skillsHelper->calculatePersistenceScore($context->getUserName(), $raw, $debug)],
		    ],
		    'class' => [
			['id' => 'time', 'score' => 5],
			['id' => 'activity', 'score' => 5],
			['id' => 'consistency', 'score' => 5],
			['id' => 'awareness', 'score' => 5],
			['id' => 'deepLearning', 'score' => 5],
			['id' => 'persistence', 'score' => 5],
		    ]
		];
		echo json_encode($stats);
	}

	public function time_graphAction($debug = false) {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}

		// We want to show points (0 if no data for that day), for the past 2 weeks
		$historyPoints = [];
		for ($i=14; $i>=1; $i--) {
			$formattedDate = date('M j', strtotime("-$i days"));
			// Array to hold 6 scores
			$historyPoints[$formattedDate] = [$formattedDate, 0, 0, 0, 0, 0, 0];
		}
			
		$email = $context->getUserName();
		// Fetch skill history items for the current student
		$historyResults = SkillHistory::find([
			"email = '$email'",
			"order" => 'time_stored ASC'
		]);
		// Go through each, and if it's in our historyPoints array, set the score.
		// Doing it this way avoids duplicate data points (if historical skill saver ran twice in a day), or empty points, since all are initialized above
		foreach ($historyResults as $day) {
			// Scores are saved at 3am, so they actually correspond to the previous day
			$formattedDate = date('M j', strtotime('-1 day', strtotime($day->time_stored)));
			if (isset($historyPoints[$formattedDate])) {
				$historyPoints[$formattedDate] = [
					$formattedDate,
					$day->time,
					$day->activity,
					$day->consistency,
					$day->awareness,
					$day->deep_learning,
					$day->persistence,
				];
			}

			if ($debug) {
				echo $day->time."\n";
				echo $day->activity."\n";
				echo $day->consistency."\n";
				echo $day->awareness."\n";
				echo $day->deep_learning."\n";
				echo $day->persistence."\n";
				echo $day->time_stored."\n";
				echo $formattedDate."\n";
				echo $day->email."\n";
			}
		}
		if ($debug) {
			print_r($historyPoints);
		}

		// Output data as csv so that we only have to send header information once
		if (!$debug) {
			header("Content-Type: text/csv");
		}
		$output = fopen("php://output", "w");
		// Header row
		fputcsv($output, ["date", "Time Management", "Online Activity", "Consistency", "Knowledge Awareness", "Deep Learning", "Persistence"]);
		foreach ($historyPoints as $row) {
			fputcsv($output, $row); // here you can change delimiter/enclosure
		}
		fclose($output);
	}
}
