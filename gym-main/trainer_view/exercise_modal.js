function openExerciseModal(sessionData) {
	const modal = document.getElementById('exerciseModal');
	const memberName = document.getElementById('modalMemberName');
	const sessionInfo = document.getElementById('modalSessionInfo');
	const content = document.getElementById('modalExerciseContent');
	
	console.log('Opening modal for session:', sessionData.session_id);
	
	// Set member name and session info
	memberName.textContent = sessionData.member_name;
	
	const sessionDate = new Date(sessionData.session_date + 'T00:00:00');
	const dayName = sessionDate.toLocaleDateString('en-US', { weekday: 'long' });
	const formattedDate = sessionDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
	const sessionTime = new Date('2000-01-01T' + sessionData.session_time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
	
	sessionInfo.textContent = `${dayName}, ${formattedDate} at ${sessionTime}`;
	
	// Parse program data
	if (!sessionData.program_data || sessionData.program_data === 'null') {
		content.innerHTML = `
			<div class="no-program-message">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c.866 1.5 2.926 3.374 5.555 3.374 2.763 0 5.144 1.093 6.342 2.135a4.5 4.5 0 0 0 5.686-4.172M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6-3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 13.5h13.5"/>
				</svg>
				<p>No workout program available for this member</p>
			</div>
		`;
		modal.classList.add('active');
		return;
	}
	
	try {
		const program = JSON.parse(sessionData.program_data);
		const exercises = program[dayName] || [];
		
		if (exercises.length === 0) {
			content.innerHTML = `
				<div class="no-program-message">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
					</svg>
					<p>No exercises scheduled for ${dayName}</p>
				</div>
			`;
		} else {
			let tableHTML = `
				<table class="exercise-table">
					<thead>
						<tr>
							<th style="width: 40px;">Done</th>
							<th>Exercise</th>
							<th>Sets</th>
							<th>Reps</th>
							<th>Rest</th>
							<th>Notes</th>
						</tr>
					</thead>
					<tbody>
			`;
			
			exercises.forEach((exercise, index) => {
				tableHTML += `
					<tr>
						<td style="text-align: center;">
							<input type="checkbox" class="exercise-checkbox" data-session-id="${sessionData.session_id}" data-exercise-index="${index}" data-exercise-name="${exercise.exercise || 'Exercise'}" />
						</td>
						<td class="exercise-name">${exercise.exercise || '-'}</td>
						<td class="exercise-sets">${exercise.sets || '-'}</td>
						<td class="exercise-reps">${exercise.reps || '-'}</td>
						<td class="exercise-rest">${exercise.rest || '-'}</td>
						<td class="exercise-notes">${exercise.notes || '-'}</td>
					</tr>
				`;
			});
			
			tableHTML += `
					</tbody>
				</table>
			`;
			
			content.innerHTML = tableHTML;
			
			// Load completion status after rendering - with a small delay to ensure DOM is ready
			setTimeout(function() {
				loadExerciseCompletion(sessionData.session_id);
				
				// Add event listeners to checkboxes
				document.querySelectorAll('.exercise-checkbox').forEach(checkbox => {
					checkbox.addEventListener('change', function() {
						console.log('Checkbox changed:', this.dataset.exerciseIndex, this.checked);
						toggleExerciseCompletion(
							this.dataset.sessionId,
							this.dataset.exerciseIndex,
							this.dataset.exerciseName,
							this.checked
						);
					});
				});
			}, 100);
		}
	} catch (e) {
		console.error('Error parsing program data:', e);
		content.innerHTML = `
			<div class="no-program-message">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
				</svg>
				<p>Error loading workout program</p>
			</div>
		`;
	}
	
	modal.classList.add('active');
}

function closeExerciseModal() {
	const modal = document.getElementById('exerciseModal');
	modal.classList.remove('active');
}

function loadExerciseCompletion(sessionId) {
	const formData = new FormData();
	formData.append('action', 'get');
	formData.append('session_id', sessionId);
	
	fetch('/gym/api/exercise_completion.php', {
		method: 'POST',
		body: formData
	})
	.then(response => {
		if (!response.ok) {
			throw new Error('Network response was not ok');
		}
		return response.json();
	})
	.then(data => {
		if (data.success && data.completions) {
			console.log('Loaded completions:', data.completions);
			Object.keys(data.completions).forEach(index => {
				const checkbox = document.querySelector(`[data-exercise-index="${index}"]`);
				if (checkbox) {
					checkbox.checked = data.completions[index];
					console.log(`Set checkbox ${index} to ${data.completions[index]}`);
				}
			});
		}
	})
	.catch(error => {
		console.error('Error loading exercise completion:', error);
		alert('Error loading exercise status: ' + error.message);
	});
}

function toggleExerciseCompletion(sessionId, exerciseIndex, exerciseName, isCompleted) {
	const formData = new FormData();
	formData.append('action', 'toggle');
	formData.append('session_id', sessionId);
	formData.append('exercise_index', exerciseIndex);
	formData.append('exercise_name', exerciseName);
	formData.append('is_completed', isCompleted ? 1 : 0);
	
	fetch('/gym/api/exercise_completion.php', {
		method: 'POST',
		body: formData
	})
	.then(response => {
		if (!response.ok) {
			throw new Error('Network response was not ok');
		}
		return response.json();
	})
	.then(data => {
		if (!data.success) {
			console.error('Error updating exercise:', data.error);
			alert('Error saving exercise status: ' + (data.error || 'Unknown error'));
		} else {
			console.log('Exercise updated:', data.message);
		}
	})
	.catch(error => {
		console.error('Error updating exercise completion:', error);
		alert('Error updating exercise: ' + error.message);
	});
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
	const modal = document.getElementById('exerciseModal');
	if (modal) {
		modal.addEventListener('click', function(e) {
			if (e.target === this) {
				closeExerciseModal();
			}
		});
	}
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
	if (e.key === 'Escape') {
		closeExerciseModal();
	}
});
