<?php
/**
 * Export Utility Functions
 * 
 * This file contains functions for exporting data to CSV and other formats
 */

/**
 * Export intern timesheet data to CSV
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The ID of the intern
 * @return array Associative array containing filename and CSV content
 */
function exportInternTimesheetToCSV($conn, $intern_id) {
    // Get intern name for the filename
    $name_stmt = $conn->prepare("SELECT Intern_Name FROM interns WHERE Intern_id = :intern_id");
    $name_stmt->bindParam(':intern_id', $intern_id);
    $name_stmt->execute();
    $intern_data = $name_stmt->fetch(PDO::FETCH_ASSOC);
    $intern_name = $intern_data['Intern_Name'];
    
    // Fetch timesheet data
    $export_stmt = $conn->prepare("SELECT t.*, i.Intern_School as intern_school, DATE(NOW()) as render_date 
                                  FROM timesheet t 
                                  JOIN interns i ON t.intern_id = i.Intern_id 
                                  WHERE t.intern_id = :intern_id");
    $export_stmt->bindParam(':intern_id', $intern_id);
    $export_stmt->execute();
    
    // Create CSV content
    $filename = str_replace(' ', '_', $intern_name) . "_timesheet_" . date('Y-m-d') . ".csv";
    $csv_content = "Date,Student Name,School,AM Time In,AM Time Out,PM Time In,PM Time Out,AM Hours,PM Hours,Overtime Hours,Total Hours\n";
    
    while ($row = $export_stmt->fetch(PDO::FETCH_ASSOC)) {
        $csv_content .= date('Y-m-d', strtotime($row['created_at'])) . ",";
        $csv_content .= $row['intern_name'] . ",";
        $csv_content .= $row['intern_school'] . ",";
        $csv_content .= formatTime($row['am_timein']) . ",";
        $csv_content .= formatTime($row['am_timeOut']) . ",";
        $csv_content .= formatTime($row['pm_timein']) . ",";
        $csv_content .= formatTime($row['pm_timeout']) . ",";
        $csv_content .= isTimeEmpty($row['am_hours_worked']) ? "-," : formatDuration($row['am_hours_worked']) . ",";
        $csv_content .= isTimeEmpty($row['pm_hours_worked']) ? "-," : formatDuration($row['pm_hours_worked']) . ",";
        $csv_content .= (isset($row['overtime_hours']) && !isTimeEmpty($row['overtime_hours'])) ? formatDuration($row['overtime_hours']) . "," : "-,";
        $csv_content .= isTimeEmpty($row['day_total_hours']) ? "-\n" : formatDuration($row['day_total_hours']) . "\n";
    }
    
    return [
        'filename' => $filename,
        'content' => $csv_content
    ];
}

/**
 * Export attendance summary report for all interns
 * 
 * @param PDO $conn Database connection
 * @param string $date_from Start date for export (YYYY-MM-DD)
 * @param string $date_to End date for export (YYYY-MM-DD)
 * @return array Associative array containing filename and CSV content
 */
function exportAttendanceSummaryToCSV($conn, $date_from = null, $date_to = null) {
    // Set default dates if not provided
    if (!$date_from) {
        $date_from = date('Y-m-01'); // First day of current month
    }
    if (!$date_to) {
        $date_to = date('Y-m-t'); // Last day of current month
    }
    
    // Get all interns
    $interns_stmt = $conn->prepare("SELECT * FROM interns ORDER BY Intern_Name ASC");
    $interns_stmt->execute();
    $interns = $interns_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create CSV content
    $filename = "attendance_summary_" . date('Y-m-d') . ".csv";
    $csv_content = "Student Name,School,Required Hours,Total Hours Rendered,Days Present,Average Hours Per Day\n";
    
    foreach ($interns as $intern) {
        // Get total hours for this intern within date range
        $hours_stmt = $conn->prepare("SELECT 
            SUM(TIME_TO_SEC(day_total_hours)) as total_seconds,
            COUNT(*) as days_present
            FROM timesheet 
            WHERE intern_id = :intern_id 
            AND DATE(created_at) BETWEEN :date_from AND :date_to");
        $hours_stmt->bindParam(':intern_id', $intern['Intern_id']);
        $hours_stmt->bindParam(':date_from', $date_from);
        $hours_stmt->bindParam(':date_to', $date_to);
        $hours_stmt->execute();
        $hours_data = $hours_stmt->fetch(PDO::FETCH_ASSOC);
        
        $total_seconds = $hours_data['total_seconds'] ?: 0;
        $days_present = $hours_data['days_present'] ?: 0;
        
        // Convert seconds to time format
        $total_hours = secondsToTime($total_seconds);
        
        // Calculate average hours per day
        $avg_seconds = ($days_present > 0) ? ($total_seconds / $days_present) : 0;
        $avg_hours = secondsToTime($avg_seconds);
        
        // Add row to CSV
        $csv_content .= $intern['Intern_Name'] . ",";
        $csv_content .= $intern['Intern_School'] . ",";
        $csv_content .= $intern['Required_Hours_Rendered'] . ",";
        $csv_content .= formatDuration($total_hours) . ",";
        $csv_content .= $days_present . ",";
        $csv_content .= formatDuration($avg_hours) . "\n";
    }
    
    return [
        'filename' => $filename,
        'content' => $csv_content
    ];
}

/**
 * Output CSV content to browser for download
 * 
 * @param string $filename The filename for the download
 * @param string $content The CSV content to download
 * @return void
 */
function outputCSVForDownload($filename, $content) {
    // Output CSV headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Output CSV content
    echo $content;
    exit();
}

/**
 * Format time for CSV export in a readable format
 *
 * @param string $time The time to format
 * @return string Formatted time or dash if empty
 */
function formatTimeForCSV($time) {
    if (isTimeEmpty($time)) {
        return '-';
    }
    
    // Convert to DateTime object
    $time_obj = new DateTime($time);
    
    // Format as 12-hour time with AM/PM
    return $time_obj->format('h:i A');
}

/**
 * Format duration for CSV export in a readable format
 *
 * @param string $duration The duration in HH:MM:SS format
 * @return string Formatted duration or dash if empty
 */
function formatDurationForCSV($duration) {
    if (isTimeEmpty($duration)) {
        return '-';
    }
    
    // Clean up the duration string to handle microseconds
    $duration = preg_replace('/\.\d+/', '', $duration);
    
    // Parse the duration components
    $parts = explode(':', $duration);
    $hours = (int)$parts[0];
    $minutes = (int)$parts[1];
    
    // Format for CSV (simpler than screen display)
    return sprintf('%d.%d', $hours, $minutes);
}
?>