<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PropertyQuestionField;
use App\Models\PropertyQuestionFieldValue;
use Illuminate\Support\Facades\DB;

class UpdateHotelFeedbackQuestions extends Command
{
    protected $signature = 'feedback:update-hotel-questions';

    protected $description = 'Ensure hotel feedback questions use dropdown scale 1-5 and update question texts';

    public function handle()
    {
        $this->info('Updating hotel (classification 5) feedback questions...');

        DB::beginTransaction();
        try {
            $scale = ['1', '2', '3', '4', '5'];

            // 1) Rename: "How frequently do you travel?" -> "Do you travel Frequently"
            $renamed = PropertyQuestionField::where('property_classification', 5)
                ->where('name', 'How frequently do you travel?')
                ->first();
            if ($renamed) {
                $renamed->name = 'Do you travel Frequently';
                $renamed->save();
                $this->info('Renamed question: How frequently do you travel? -> Do you travel Frequently');
            }

            // 2) Convert any Satisfied/Not Satisfied to dropdown 1-5
            $candidates = PropertyQuestionField::where('property_classification', 5)
                ->whereIn('field_type', ['radio', 'dropdown'])
                ->with('field_values')
                ->get();
            foreach ($candidates as $field) {
                $values = $field->field_values->pluck('value')->map(fn($v) => trim(strtolower($v)))->toArray();
                if (in_array('satisfied', $values) || in_array('not satisfied', $values)) {
                    // Replace with 1-5 dropdown
                    $field->field_type = 'dropdown';
                    $field->save();
                    PropertyQuestionFieldValue::where('property_question_field_id', $field->id)->delete();
                    foreach ($scale as $v) {
                        PropertyQuestionFieldValue::create([
                            'property_question_field_id' => $field->id,
                            'value' => $v,
                        ]);
                    }
                    $this->info("Converted '{$field->name}' to dropdown 1-5");
                }
            }

            // 3) Ensure the required dropdown questions exist with 1-5 scale
            $requiredQuestions = [
                'Do you travel Frequently',
                'How accessible and convenient were the hotel amenities to use?',
                'How did the hotel facilities meet your expectations?',
                'How did you find the booking process for your stay?',
                'How satisfied are you with your overall experience in the hotel?',
            ];

            foreach ($requiredQuestions as $name) {
                $field = PropertyQuestionField::where('property_classification', 5)
                    ->where('name', $name)
                    ->first();
                if (!$field) {
                    $field = PropertyQuestionField::create([
                        'name' => $name,
                        'field_type' => 'dropdown',
                        'property_classification' => 5,
                        'status' => 'active',
                    ]);
                    foreach ($scale as $v) {
                        PropertyQuestionFieldValue::create([
                            'property_question_field_id' => $field->id,
                            'value' => $v,
                        ]);
                    }
                    $this->info("Created question: {$name}");
                } else {
                    // Ensure dropdown type and 1-5 values
                    $updated = false;
                    if ($field->field_type !== 'dropdown') {
                        $field->field_type = 'dropdown';
                        $updated = true;
                    }
                    $existing = $field->field_values()->pluck('value')->toArray();
                    sort($existing);
                    $needReset = $existing !== $scale;
                    if ($updated) {
                        $field->save();
                    }
                    if ($needReset) {
                        PropertyQuestionFieldValue::where('property_question_field_id', $field->id)->delete();
                        foreach ($scale as $v) {
                            PropertyQuestionFieldValue::create([
                                'property_question_field_id' => $field->id,
                                'value' => $v,
                            ]);
                        }
                        $this->info("Updated choices to 1-5 for: {$name}");
                    }
                }
            }

            DB::commit();
            $this->info('Hotel feedback questions are up to date.');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}


