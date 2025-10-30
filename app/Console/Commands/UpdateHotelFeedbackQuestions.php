<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PropertyQuestionField;
use App\Models\PropertyQuestionFieldValue;

class UpdateHotelFeedbackQuestions extends Command
{
    protected $signature = 'feedback:update-hotel-questions';

    protected $description = 'Update hotel feedback questions to dropdown scale 1-5 and set required labels';

    public function handle()
    {
        $this->info('Updating hotel (classification 5) feedback questions to dropdown 1-5...');

        $desiredQuestions = [
            'Do you travel Frequently',
            'how accessible and convenient were the hotel amenities to use?',
            'How did the hotel facilities meet your expectations?',
            'How did you find the booking process for your stay?',
            'How satisfied are you with your overall experience in the hotel?',
            "How satisfied were you with the quality of the food served at the hotel's restaurant?",
        ];

        $scaleOptions = ['1', '2', '3', '4', '5'];

        DB::beginTransaction();
        try {
            // 1) Rename specific existing question if present
            $renamed = PropertyQuestionField::where('property_classification', 5)
                ->where('name', 'How frequently do you travel?')
                ->first();
            if ($renamed) {
                $renamed->name = 'Do you travel Frequently';
                $renamed->field_type = 'dropdown';
                $renamed->save();
                // replace options with 1..5
                PropertyQuestionFieldValue::where('property_question_field_id', $renamed->id)->delete();
                foreach ($scaleOptions as $opt) {
                    PropertyQuestionFieldValue::create([
                        'property_question_field_id' => $renamed->id,
                        'value' => $opt,
                    ]);
                }
                $this->info('Renamed and updated: How frequently do you travel? -> Do you travel Frequently');
            }

            // 2) Convert any Satisfied/Not Satisfied fields to dropdown 1..5
            $candidates = PropertyQuestionField::where('property_classification', 5)
                ->with('field_values')
                ->get();
            foreach ($candidates as $field) {
                $values = $field->field_values->pluck('value')->map(function ($v) { return trim(strtolower($v)); })->all();
                if (!empty($values) && in_array('satisfied', $values) && (in_array('not satisfied', $values) || in_array('unsatisfied', $values))) {
                    $field->field_type = 'dropdown';
                    $field->save();
                    PropertyQuestionFieldValue::where('property_question_field_id', $field->id)->delete();
                    foreach ($scaleOptions as $opt) {
                        PropertyQuestionFieldValue::create([
                            'property_question_field_id' => $field->id,
                            'value' => $opt,
                        ]);
                    }
                    $this->info("Converted '{$field->name}' to dropdown 1-5");
                }
            }

            // 3) Ensure all desired questions exist as dropdown 1..5
            foreach ($desiredQuestions as $q) {
                $field = PropertyQuestionField::firstOrCreate([
                    'property_classification' => 5,
                    'name' => $q,
                ], [
                    'field_type' => 'dropdown',
                    'status' => 'active',
                ]);

                // If field exists but not dropdown, force to dropdown
                if ($field->field_type !== 'dropdown') {
                    $field->field_type = 'dropdown';
                    $field->save();
                }

                // Replace options with 1..5
                PropertyQuestionFieldValue::where('property_question_field_id', $field->id)->delete();
                foreach ($scaleOptions as $opt) {
                    PropertyQuestionFieldValue::create([
                        'property_question_field_id' => $field->id,
                        'value' => $opt,
                    ]);
                }
                $this->info("Ensured question: '{$q}' with 1-5 scale");
            }

            DB::commit();
            $this->info('Hotel feedback questions updated successfully.');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed to update questions: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}


