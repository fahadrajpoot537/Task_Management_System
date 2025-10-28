<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">ZKTeco Attendance Manager</h1>
        
        <!-- Connection Settings -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <h2 class="text-xl font-semibold mb-4">Device Settings</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Device IP</label>
                    <input type="text" wire:model="ip" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Port</label>
                    <input type="number" wire:model="port" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <button wire:click="connect" 
                    wire:loading.attr="disabled" 
                    class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50">
                <span wire:loading.remove wire:target="connect">Connect to Device</span>
                <span wire:loading wire:target="connect">Connecting...</span>
            </button>
        </div>

        <!-- Status Messages -->
        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if ($error)
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ $error }}
            </div>
        @endif

        <!-- Device Info -->
        @if ($isConnected && !empty($deviceInfo))
            <div class="bg-blue-50 rounded-lg p-4 mb-6">
                <h2 class="text-xl font-semibold mb-4">Device Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><strong>Version:</strong> {{ $deviceInfo['version'] ?? 'N/A' }}</div>
                    <div><strong>Platform:</strong> {{ $deviceInfo['platform'] ?? 'N/A' }}</div>
                    <div><strong>OS:</strong> {{ $deviceInfo['os'] ?? 'N/A' }}</div>
                    <div><strong>Serial:</strong> {{ $deviceInfo['serial'] ?? 'N/A' }}</div>
                </div>
                <div class="mt-2"><strong>Users:</strong> {{ count($users) }} found</div>
            </div>
        @endif

        <!-- Actions -->
        @if ($isConnected)
            <div class="flex flex-wrap gap-4 mb-6">
                <button wire:click="fetchAttendance" 
                        wire:loading.attr="disabled" 
                        class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="fetchAttendance">Fetch Attendance</span>
                    <span wire:loading wire:target="fetchAttendance">Fetching...</span>
                </button>
                
                @if (!empty($attendance))
                    <button wire:click="calculateAttendance" 
                            wire:loading.attr="disabled"
                            class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="calculateAttendance">Calculate Attendance</span>
                        <span wire:loading wire:target="calculateAttendance">Calculating...</span>
                    </button>
                    
                    <button wire:click="saveAttendanceToDatabase" 
                            wire:loading.attr="disabled"
                            class="bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="saveAttendanceToDatabase">💾 Save to Database</span>
                        <span wire:loading wire:target="saveAttendanceToDatabase">Saving...</span>
                    </button>
                    
                    <button wire:click="exportCsv" 
                            class="bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700">
                        Export CSV
                    </button>
                    
                    <button wire:click="clearAttendance" 
                            wire:loading.attr="disabled"
                            onclick="return confirm('Are you sure you want to clear all attendance records?')"
                            class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="clearAttendance">Clear Attendance</span>
                        <span wire:loading wire:target="clearAttendance">Clearing...</span>
                    </button>
                @endif
            </div>
        @endif

        <!-- Loading Indicator -->
        @if ($loading)
            <div class="flex justify-center items-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-2 text-gray-600">Processing...</span>
            </div>
        @endif

        <!-- Attendance Table -->
        @if (!empty($attendance))
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <h2 class="text-xl font-semibold p-4 bg-gray-50">Attendance Records ({{ count($attendance) }} total)</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">State</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verify</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Workcode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reserved</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($attendance as $row)
                               
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['id'] ?? '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['timestamp'] ?? '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['state'] ?? 0 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['verify'] ?? 0 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['workcode'] ?? 0 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['reserved'] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Users Table -->
        @if (!empty($users))
            <div class="bg-white rounded-lg shadow overflow-hidden mt-6">
                <h2 class="text-xl font-semibold p-4 bg-gray-50">Users ({{ count($users) }} total)</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Card No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check In Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check Out Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($users as $user)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user['uid'] ?? '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user['id'] ?? '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user['name'] ?? '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user['role'] ?? 0 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user['cardno'] ?? '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($editingUser == $user['uid'])
                                            <input type="time" wire:model="editCheckIn" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        @else
                                            {{ $workingHours[$user['uid']]['check_in_time'] ?? 'Not set' }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($editingUser == $user['uid'])
                                            <input type="time" wire:model="editCheckOut" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        @else
                                            {{ $workingHours[$user['uid']]['check_out_time'] ?? 'Not set' }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($editingUser == $user['uid'])
                                            <div class="flex space-x-2">
                                                <button wire:click="saveWorkingHours" class="text-green-600 hover:text-green-800 text-sm">Save</button>
                                                <button wire:click="cancelEdit" class="text-red-600 hover:text-red-800 text-sm">Cancel</button>
                                            </div>
                                        @else
                                            <div class="flex flex-col space-y-1">
                                                <button wire:click="editWorkingHours('{{ $user['uid'] }}')" class="text-blue-600 hover:text-blue-800 text-sm">Edit</button>
                                                @if($workingHours[$user['uid']]['user_id'])
                                                    <span class="text-xs text-gray-500">ID: {{ $workingHours[$user['uid']]['user_id'] }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Attendance Calculations -->
        @if (!empty($attendanceCalculations))
            <div class="bg-white rounded-lg shadow overflow-hidden mt-6">
                <h2 class="text-xl font-semibold p-4 bg-gray-50">Attendance Calculations</h2>
                <div class="overflow-x-auto">
                    @foreach ($attendanceCalculations as $uid => $data)
                        @if (isset($data['summary']))
                            @php
                                $user = collect($users)->firstWhere('uid', $uid);
                                $summary = $data['summary'];
                                unset($data['summary']);
                            @endphp
                            
                            <div class="border-b border-gray-200 p-4">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $user['name'] ?? 'Unknown User' }} (UID: {{ $uid }})</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div class="bg-blue-50 p-3 rounded">
                                        <div class="text-sm text-gray-600">Total Days</div>
                                        <div class="text-xl font-bold text-blue-600">{{ $summary['total_days'] }}</div>
                                    </div>
                                    <div class="bg-green-50 p-3 rounded">
                                        <div class="text-sm text-gray-600">Total Hours</div>
                                        <div class="text-xl font-bold text-green-600">{{ $summary['total_hours'] }}</div>
                                    </div>
                                    <div class="bg-purple-50 p-3 rounded">
                                        <div class="text-sm text-gray-600">Avg Hours/Day</div>
                                        <div class="text-xl font-bold text-purple-600">{{ $summary['average_hours_per_day'] }}</div>
                                    </div>
                                </div>
                                
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Expected Check In</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actual Check In</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Expected Check Out</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actual Check Out</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Hours Worked</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach ($data as $day)
                                                <tr>
                                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $day['date'] }}</td>
                                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $day['expected_check_in'] }}</td>
                                                    <td class="px-4 py-2 text-sm {{ $day['late_arrival'] ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                                                        {{ $day['actual_check_in'] }}
                                                        @if($day['late_arrival'])
                                                            <span class="text-xs">(Late)</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $day['expected_check_out'] }}</td>
                                                    <td class="px-4 py-2 text-sm {{ $day['early_departure'] ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                                                        {{ $day['actual_check_out'] }}
                                                        @if($day['early_departure'])
                                                            <span class="text-xs">(Early)</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $day['hours_worked'] }} hrs</td>
                                                    <td class="px-4 py-2 text-sm">
                                                        @if($day['late_arrival'] || $day['early_departure'])
                                                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Irregular</span>
                                                        @else
                                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Regular</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
