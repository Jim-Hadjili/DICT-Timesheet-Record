<div class="bg-white rounded-xl shadow-lg overflow-hidden hover-card border-2 border-blue-300">
                    <div class="p-4 bg-gray-50 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-table text-primary-600 mr-2"></i>
                            Timesheet Records
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">Intern Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">AM Time In</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">AM Time Out</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">PM Time In</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">PM Time Out</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">AM Hours</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">PM Hours</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">Total Hours</th>
                                     <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if(!empty($selected_intern_id) && $timesheet_data): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('M d, Y', strtotime($timesheet_data['render_date'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $timesheet_data['intern_name']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo formatTime($timesheet_data['am_timein']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo formatTime($timesheet_data['am_timeOut']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo formatTime($timesheet_data['pm_timein']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo formatTime($timesheet_data['pm_timeout']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo isTimeEmpty($timesheet_data['am_hours_worked']) ? '-' : formatDuration($timesheet_data['am_hours_worked']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo isTimeEmpty($timesheet_data['pm_hours_worked']) ? '-' : formatDuration($timesheet_data['pm_hours_worked']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-primary-600">
                                            <?php echo isTimeEmpty($timesheet_data['day_total_hours']) ? '-' : formatDuration($timesheet_data['day_total_hours']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <button class="bg-blue-500 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 focus:outline-none focus:shadow-outline" onclick="openModal('addNotesModal', '<?php echo $timesheet_data['intern_id']; ?>', '<?php echo $timesheet_data['render_date']; ?>')">
                                                Add Notes
                                            </button>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="px-6 py-10 text-center text-sm text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-3"></i>
                                                <p>No records found</p>
                                                <?php if(empty($selected_intern_id)): ?>
                                                    <p class="text-xs mt-1">Please select an Intern to view their timesheet data</p>
                                                <?php else: ?>
                                                    <p class="text-xs mt-1">No timesheet entries found for this Intern</p>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>