<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Collection;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\Log;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected static ?string $title = 'Attendance';

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([

                Action::make('recordAttendance')
                ->label('Record Attendance')
                ->form([
                    TextInput::make('attendance_code')
                        ->label('Attendance Code')
                        ->required(),
                ])
                ->action(function ($data) {
                    // Find the employee based on the attendance code
                    $employee = \App\Models\Employee::where('attendance_code', $data['attendance_code'])->first();
                    
                    if ($employee) {
                        // Get the current date and time
                        $now = Carbon::now();
                        $currentDate = $now->toDateString(); // Current date in Y-m-d format
                        $currentTime = $now->format('H:i:s'); // Current time in H:i:s format
                        
                        // Determine if the current time is morning or afternoon
                        $isMorning = $now->isBefore($now->copy()->setTime(12, 0));
                        $isAfternoon = !$isMorning;

                        // Retrieve the existing attendance record if it exists
                        $attendance = \App\Models\Attendance::where('Employee_ID', $employee->id)
                            ->where('Date', $currentDate)
                            ->first();

                        // Initialize $totalMinutes
                        $totalMinutes = 0;

                        if ($attendance) {
                            // Update existing record
                            if ($isMorning) {
                                if (!$attendance->Checkin_One) {
                                    // If no morning check-in, set check-in
                                    $attendance->Checkin_One = $currentTime;
                                } elseif (!$attendance->Checkout_One) {
                                    // If morning check-in exists but no checkout, set checkout
                                    $attendance->Checkout_One = $currentTime;
                                }
                            } else {
                                if (!$attendance->Checkin_Two) {
                                    // If no afternoon check-in, set check-in
                                    $attendance->Checkin_Two = $currentTime;
                                } elseif (!$attendance->Checkout_Two) {
                                    // If afternoon check-in exists but no checkout, set checkout
                                    $attendance->Checkout_Two = $currentTime;
                                }
                            }

                            // Calculate Total Hours
                            if ($attendance->Checkin_One && $attendance->Checkout_One) {
                                $checkinOne = Carbon::parse($attendance->Checkin_One);
                                $checkoutOne = Carbon::parse($attendance->Checkout_One);

                                // Ensure checkout is after checkin
                                if ($checkoutOne->greaterThan($checkinOne)) {
                                    $minutesOne = $checkoutOne->diffInMinutes($checkinOne, true); // Minutes difference
                                    $totalMinutes += $minutesOne;
                                } else {
                                    Log::error('Checkout time is earlier than checkin time for morning shift.');
                                }
                            }

                            if ($attendance->Checkin_Two && $attendance->Checkout_Two) {
                                $checkinTwo = Carbon::parse($attendance->Checkin_Two);
                                $checkoutTwo = Carbon::parse($attendance->Checkout_Two);

                                // Ensure checkout is after checkin
                                if ($checkoutTwo->greaterThan($checkinTwo)) {
                                    $minutesTwo = $checkoutTwo->diffInMinutes($checkinTwo, true); // Minutes difference
                                    $totalMinutes += $minutesTwo;
                                } else {
                                    Log::error('Checkout time is earlier than checkin time for afternoon shift.');
                                }
                            }
                            
                            // Convert minutes to hours and update Total Hours
                            $attendance->Total_Hours = $totalMinutes / 60;
                            $attendance->save();
                        } else {
                            // Create a new attendance record
                            $attendance = \App\Models\Attendance::create([
                                'Employee_ID' => $employee->id,
                                'Date' => $currentDate,
                                'Checkin_One' => $isMorning ? $currentTime : null,
                                'Checkout_One' => null,
                                'Checkin_Two' => $isAfternoon ? $currentTime : null,
                                'Checkout_Two' => null,
                                'Total_Hours' => 0, // Initial total hours
                            ]);
                        }
                    } else {
                        // Handle the case where the employee is not found
                        Log::error('Employee not found for attendance code: ' . $data['attendance_code']);
                        // You could also return a user-friendly error message or feedback here
                        Notification::make()
                    ->title('Error')
                    ->body('Employee not found for the provided attendance code.')
                    ->danger() // You can use ->success() for success messages
                    ->send();
                    }
                }),


            ])->label('Attendance')->button()->size(ActionSize::Medium),
        ];
    }
}
