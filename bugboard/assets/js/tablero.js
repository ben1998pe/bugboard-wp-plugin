/**
 * JavaScript para el tablero Kanban de BugBoard
 */

jQuery(document).ready(function($) {
    
    // Variables globales
    var currentTask = null;
    var isDragging = false;
    var bugboardAjaxUrl = bugboardAjax.ajaxurl;
    var bugboardNonce = bugboardAjax.nonce;
    
    // Inicializar el tablero
    initTablero();
    
    /**
     * Inicializar el tablero
     */
    function initTablero() {
        setupEventListeners();
        setupDragAndDrop();
        loadTasks();
    }
    
    /**
     * Configurar event listeners
     */
    function setupEventListeners() {
        // Botones de añadir tarea
        $('.add-task-btn').on('click', function() {
            var status = $(this).data('status');
            openTaskModal(status);
        });
        
        // Cerrar modal
        $('.bugboard-modal-close').on('click', closeTaskModal);
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('bugboard-modal')) {
                closeTaskModal();
            }
        });
        
        // Formulario de tarea
        $('#task-form').on('submit', function(e) {
            e.preventDefault();
            saveTask();
        });
        
        // Botón de debug
        $('#debug-load-tasks').on('click', function() {
            console.log('Botón debug clickeado');
            loadTasks();
        });
        
        // Botón de prueba AJAX
        $('#debug-test-ajax').on('click', function() {
            console.log('Probando AJAX...');
            $.ajax({
                url: bugboardAjaxUrl,
                type: 'POST',
                data: {
                    action: 'bugboard_test',
                    nonce: bugboardNonce
                },
                success: function(response) {
                    console.log('Test AJAX exitoso:', response);
                    $('#debug-info').html('<strong>Test AJAX:</strong> ' + response.data.message);
                },
                error: function(xhr, status, error) {
                    console.error('Test AJAX falló:', error);
                    $('#debug-info').html('<strong>Test AJAX Error:</strong> ' + error);
                }
            });
        });
        
        // Botón de prueba Get Task
        $('#debug-test-get-task').on('click', function() {
            console.log('Probando Get Task...');
            $.ajax({
                url: bugboardAjaxUrl,
                type: 'POST',
                data: {
                    action: 'bugboard_test_get_task',
                    nonce: bugboardNonce,
                    task_id: 1
                },
                success: function(response) {
                    console.log('Test Get Task exitoso:', response);
                    $('#debug-info').html('<strong>Test Get Task:</strong> ' + response.data.message);
                },
                error: function(xhr, status, error) {
                    console.error('Test Get Task falló:', error);
                    $('#debug-info').html('<strong>Test Get Task Error:</strong> ' + error);
                }
            });
        });
    }
    
    /**
     * Configurar drag and drop
     */
    function setupDragAndDrop() {
        // Hacer las tareas arrastrables
        $(document).on('mousedown', '.bugboard-task', function(e) {
            // No activar drag si se hace clic en botones de acción
            if ($(e.target).closest('.task-actions').length > 0) {
                return;
            }
            
            currentTask = $(this);
            isDragging = true;
            
            // Crear elemento fantasma
            var ghost = currentTask.clone();
            ghost.addClass('task-ghost');
            ghost.css({
                position: 'fixed',
                zIndex: 1000,
                opacity: 0.8,
                pointerEvents: 'none'
            });
            
            $('body').append(ghost);
            
            // Ocultar tarea original
            currentTask.addClass('task-dragging');
            
            $(document).on('mousemove', handleMouseMove);
            $(document).on('mouseup', handleMouseUp);
        });
        
        // Configurar zonas de drop
        $('.bugboard-tasks').on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('drop-zone-active');
        });
        
        $('.bugboard-tasks').on('dragleave', function(e) {
            $(this).removeClass('drop-zone-active');
        });
    }
    
    /**
     * Manejar movimiento del mouse
     */
    function handleMouseMove(e) {
        if (!isDragging || !currentTask) return;
        
        var ghost = $('.task-ghost');
        ghost.css({
            left: e.clientX - ghost.width() / 2,
            top: e.clientY - ghost.height() / 2
        });
    }
    
    /**
     * Manejar soltar el mouse
     */
    function handleMouseUp(e) {
        console.log('handleMouseUp llamado');
        console.log('isDragging:', isDragging);
        console.log('currentTask:', currentTask);
        console.log('target:', e.target);
        
        if (!isDragging || !currentTask) {
            console.log('No se procesa - no está arrastrando o no hay tarea actual');
            return;
        }
        
        // Verificar que no se hizo clic en un botón de acción
        if ($(e.target).closest('.task-actions').length > 0) {
            console.log('Clic en botón de acción - no procesar drop');
            // Si se hizo clic en un botón de acción, no procesar el drop
            $('.task-ghost').remove();
            currentTask.removeClass('task-dragging');
            currentTask = null;
            isDragging = false;
            
            $(document).off('mousemove', handleMouseMove);
            $(document).off('mouseup', handleMouseUp);
            return;
        }
        
        var targetColumn = $(e.target).closest('.bugboard-tasks');
        console.log('targetColumn encontrado:', targetColumn.length > 0);
        
        if (targetColumn.length > 0) {
            var newStatus = targetColumn.closest('.bugboard-column').data('status');
            var taskId = currentTask.data('task-id');
            var oldStatus = currentTask.data('status');
            
            // Verificar si hay elementos duplicados antes de procesar
            var allElementsWithId = $('.bugboard-task[data-task-id="' + taskId + '"]');
            if (allElementsWithId.length > 1) {
                console.log('¡ADVERTENCIA! Se encontraron elementos duplicados antes del drop. Limpiando...');
                allElementsWithId.not(':first').remove();
                currentTask = $('.bugboard-task[data-task-id="' + taskId + '"]').first();
            }
            
            // Solo actualizar si el estado cambió
            if (oldStatus !== newStatus) {
                console.log('Llamando updateTaskStatus con:', { taskId: taskId, newStatus: newStatus });
                updateTaskStatus(taskId, newStatus);
            }
        }
        
        // Limpiar
        $('.task-ghost').remove();
        currentTask.removeClass('task-dragging');
        currentTask = null;
        isDragging = false;
        
        $(document).off('mousemove', handleMouseMove);
        $(document).off('mouseup', handleMouseUp);
    }
    
    /**
     * Cargar tareas
     */
    function loadTasks() {
        console.log('Cargando tareas...');
        console.log('URL:', bugboardAjaxUrl);
        console.log('Nonce:', bugboardNonce);
        
        var requestData = {
            action: 'bugboard_get_tasks',
            nonce: bugboardNonce
        };
        
        console.log('Datos de la petición:', requestData);
        
        $.ajax({
            url: bugboardAjaxUrl,
            type: 'POST',
            data: requestData,
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                if (response.success) {
                    console.log('Tareas cargadas:', response.data);
                    renderTasks(response.data);
                } else {
                    console.error('Error en la respuesta:', response);
                    $('#debug-info').html('<strong>Error:</strong> ' + (response.data ? response.data.message : 'Error desconocido'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar las tareas:', error);
                console.error('Status:', status);
                console.error('XHR:', xhr);
                console.error('Response Text:', xhr.responseText);
                
                $('#debug-info').html('<strong>Error AJAX:</strong> ' + error + ' - Status: ' + status);
            }
        });
    }
    
    /**
     * Renderizar tareas en el tablero
     */
    function renderTasks(tasks) {
        console.log('Renderizando tareas:', tasks);
        
        // Mostrar información de debug
        $('#debug-info').html('<strong>Debug:</strong> Se encontraron ' + tasks.length + ' tareas');
        
        // Limpiar todas las columnas
        $('.bugboard-tasks').empty();
        
        // Verificar que no haya elementos duplicados antes de renderizar
        var existingTasks = $('.bugboard-task');
        if (existingTasks.length > 0) {
            console.log('¡ADVERTENCIA! Se encontraron elementos de tarea antes de renderizar. Limpiando...');
            existingTasks.remove();
        }
        
        // Agrupar tareas por estado
        var tasksByStatus = {};
        tasks.forEach(function(task) {
            if (!tasksByStatus[task.status]) {
                tasksByStatus[task.status] = [];
            }
            tasksByStatus[task.status].push(task);
        });
        
        console.log('Tareas agrupadas por estado:', tasksByStatus);
        
        // Renderizar tareas en cada columna
        $('.bugboard-column').each(function() {
            var status = $(this).data('status');
            var columnTasks = tasksByStatus[status] || [];
            var taskContainer = $(this).find('.bugboard-tasks');
            var countElement = $(this).find('.task-count');
            
            console.log('Columna ' + status + ':', columnTasks.length + ' tareas');
            
            // Actualizar contador
            countElement.text(columnTasks.length);
            
            // Renderizar tareas
            columnTasks.forEach(function(task) {
                var taskElement = createTaskElement(task);
                taskContainer.append(taskElement);
            });
        });
    }
    
    /**
     * Crear elemento de tarea
     */
    function createTaskElement(task) {
        var priorityClass = 'priority-' + task.priority;
        var assigneeName = task.assignee_name || 'Sin asignar';
        
        // Formatear fecha
        var taskDate = '';
        if (task.created_at) {
            var date = new Date(task.created_at);
            taskDate = date.toLocaleDateString('es-ES');
        }
        
        var taskHtml = `
            <div class="bugboard-task ${priorityClass}" data-task-id="${task.id}" data-status="${task.status}">
                <div class="task-header">
                    <h4 class="task-title">${task.title}</h4>
                    <div class="task-actions">
                        <button class="edit-task-btn" onclick="editTask(${task.id}); event.stopPropagation();">✏️</button>
                        <button class="delete-task-btn" onclick="deleteTask(${task.id}); event.stopPropagation();">🗑️</button>
                    </div>
                </div>
                <div class="task-content">
                    <p class="task-description">${task.description || 'Sin descripción'}</p>
                </div>
                <div class="task-footer">
                    <span class="task-priority ${priorityClass}">${task.priority}</span>
                    <span class="task-assignee">👤 ${assigneeName}</span>
                    <span class="task-date">📅 ${taskDate}</span>
                </div>
            </div>
        `;
        
        return $(taskHtml);
    }
    
    /**
     * Obtener nombre de usuario (mantenido para compatibilidad)
     */
    function getUserName(userId) {
        if (!userId || userId === '') {
            return 'Sin asignar';
        }
        
        // Lista de usuarios disponibles (se puede mejorar con AJAX)
        var users = {
            '1': 'Administrador',
            '2': 'Editor',
            '3': 'Autor'
        };
        
        return users[userId] || 'Usuario ' + userId;
    }
    
    /**
     * Abrir modal de tarea
     */
    function openTaskModal(status) {
        $('#task-id').val('');
        $('#task-status').val(status);
        $('#task-title').val('');
        $('#task-description').val('');
        $('#task-priority').val('media');
        $('#task-assignee').val('');
        $('#task-due-date').val('');
        $('#task-estimated-hours').val('');
        $('#modal-title').text('Añadir Nueva Tarea');
        
        $('.bugboard-modal').show();
    }
    
    /**
     * Cerrar modal
     */
    function closeTaskModal() {
        $('.bugboard-modal').hide();
    }
    
    /**
     * Guardar tarea (Optimizado)
     */
    function saveTask() {
        var formData = {
            task_id: $('#task-id').val(),
            title: $('#task-title').val(),
            description: $('#task-description').val(),
            status: $('#task-status').val(),
            priority: $('#task-priority').val(),
            assignee: $('#task-assignee').val(),
            due_date: $('#task-due-date').val(),
            estimated_hours: $('#task-estimated-hours').val()
        };
        
        console.log('Guardando tarea:', formData);
        
        // Cerrar modal inmediatamente para mejor UX
        closeTaskModal();
        
        var action = formData.task_id ? 'bugboard_update_task' : 'bugboard_create_task';
        
        $.ajax({
            url: bugboardAjaxUrl,
            type: 'POST',
            data: {
                action: action,
                nonce: bugboardNonce,
                ...formData
            },
            success: function(response) {
                console.log('Respuesta de guardar:', response);
                if (response.success) {
                    // Si es una tarea nueva, añadirla inmediatamente
                    if (!formData.task_id) {
                        addTaskToColumn(response.data.task.id, response.data.task);
                    } else {
                        // Si es edición, actualizar la tarea existente
                        updateTaskInColumn(response.data.task.id, response.data.task);
                    }
                    showNotification('Tarea guardada correctamente', 'success');
                } else {
                    showNotification('Error al guardar la tarea: ' + (response.data ? response.data.message : 'Error desconocido'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al guardar tarea:', error);
                console.error('Response Text:', xhr.responseText);
                showNotification('Error al guardar la tarea: ' + error, 'error');
            }
        });
    }
    
    /**
     * Actualizar estado de tarea (Optimizado)
     */
    function updateTaskStatus(taskId, newStatus) {
        console.log('updateTaskStatus llamado con:', { taskId: taskId, newStatus: newStatus });
        
        // Actualización optimista - cambiar inmediatamente en la UI
        var taskElement = $('.bugboard-task[data-task-id="' + taskId + '"]');
        var oldStatus = taskElement.data('status');
        
        console.log('Elemento encontrado:', taskElement.length);
        console.log('Estado anterior:', oldStatus);
        console.log('Estado nuevo:', newStatus);
        
        // Si ya está en el estado correcto, no hacer nada
        if (oldStatus === newStatus) {
            console.log('Ya está en el estado correcto, no hacer nada');
            return;
        }
        
        // Verificar si hay elementos duplicados
        var allElementsWithId = $('.bugboard-task[data-task-id="' + taskId + '"]');
        console.log('Elementos con el mismo ID encontrados:', allElementsWithId.length);
        
        if (allElementsWithId.length > 1) {
            console.log('¡ADVERTENCIA! Se encontraron elementos duplicados. Removiendo duplicados...');
            // Mantener solo el primer elemento y remover los demás
            allElementsWithId.not(':first').remove();
            taskElement = $('.bugboard-task[data-task-id="' + taskId + '"]').first();
        }
        
        // Mover la tarea visualmente inmediatamente
        var targetColumn = $('.bugboard-column[data-status="' + newStatus + '"] .bugboard-tasks');
        console.log('Columna objetivo encontrada:', targetColumn.length);
        
        // Mover el elemento original, no crear un clon
        taskElement.addClass('task-moving');
        targetColumn.append(taskElement);
        
        // Actualizar el estado del elemento
        taskElement.data('status', newStatus);
        
        console.log('Tarea movida visualmente');
        
        // Actualizar contadores inmediatamente
        updateColumnCounts();
        
        // Hacer la petición AJAX en background
        $.ajax({
            url: bugboardAjaxUrl,
            type: 'POST',
            data: {
                action: 'bugboard_update_task_status',
                nonce: bugboardNonce,
                task_id: taskId,
                status: newStatus
            },
            success: function(response) {
                console.log('Respuesta de updateTaskStatus:', response);
                if (response.success) {
                    // Remover clase de animación
                    taskElement.removeClass('task-moving');
                    showNotification('Estado actualizado correctamente', 'success');
                } else {
                    // Revertir cambios si falló
                    taskElement.removeClass('task-moving');
                    // Mover de vuelta a la columna original
                    var originalColumn = $('.bugboard-column[data-status="' + oldStatus + '"] .bugboard-tasks');
                    originalColumn.append(taskElement);
                    taskElement.data('status', oldStatus);
                    updateColumnCounts();
                    showNotification('Error al actualizar el estado', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en updateTaskStatus:', error);
                // Revertir cambios si falló
                taskElement.removeClass('task-moving');
                // Mover de vuelta a la columna original
                var originalColumn = $('.bugboard-column[data-status="' + oldStatus + '"] .bugboard-tasks');
                originalColumn.append(taskElement);
                taskElement.data('status', oldStatus);
                updateColumnCounts();
                showNotification('Error al actualizar el estado', 'error');
            }
        });
    }
    
    /**
     * Actualizar contadores de columnas
     */
    function updateColumnCounts() {
        $('.bugboard-column').each(function() {
            var column = $(this);
            var status = column.data('status');
            var taskCount = column.find('.bugboard-task').length;
            var emptyMessage = column.find('.empty-column-message');
            
            column.find('.task-count').text(taskCount);
            
            // Mostrar/ocultar mensaje de columna vacía
            if (taskCount === 0) {
                emptyMessage.show();
            } else {
                emptyMessage.hide();
            }
            
            console.log('Columna ' + status + ': ' + taskCount + ' tareas');
        });
    }
    
    /**
     * Añadir tarea a una columna
     */
    function addTaskToColumn(taskId, taskData) {
        var targetColumn = $('.bugboard-column[data-status="' + taskData.status + '"] .bugboard-tasks');
        var taskElement = createTaskElement(taskData);
        
        taskElement.addClass('task-new');
        targetColumn.append(taskElement);
        updateColumnCounts();
        
        // Remover clase de animación después de un tiempo
        setTimeout(function() {
            taskElement.removeClass('task-new');
        }, 2000);
    }
    
    /**
     * Actualizar tarea en una columna
     */
    function updateTaskInColumn(taskId, taskData) {
        var taskElement = $('.bugboard-task[data-task-id="' + taskId + '"]');
        if (taskElement.length > 0) {
            // Actualizar contenido de la tarea
            taskElement.find('.task-title').text(taskData.title);
            taskElement.find('.task-description').text(taskData.description || 'Sin descripción');
            taskElement.find('.task-priority').text(taskData.priority);
            taskElement.find('.task-priority').removeClass().addClass('task-priority priority-' + taskData.priority);
            
            // Si cambió el estado, mover la tarea
            var currentStatus = taskElement.data('status');
            if (currentStatus !== taskData.status) {
                updateTaskStatus(taskId, taskData.status);
            }
        }
    }
    
    /**
     * Mostrar notificación
     */
    function showNotification(message, type) {
        var notification = $('<div class="bugboard-notification ' + type + '">' + message + '</div>');
        $('body').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Funciones globales para botones
    window.editTask = function(taskId) {
        console.log('Editando tarea:', taskId);
        
        var requestData = {
            action: 'bugboard_get_task',
            nonce: bugboardNonce,
            task_id: taskId
        };
        
        console.log('Datos de la petición:', requestData);
        
        // Cargar datos de la tarea
        $.ajax({
            url: bugboardAjaxUrl,
            type: 'POST',
            data: requestData,
            success: function(response) {
                console.log('Respuesta completa:', response);
                if (response.success) {
                    var task = response.data;
                    console.log('Datos de la tarea:', task);
                    
                    // Llenar el modal con los datos
                    $('#task-id').val(task.id);
                    $('#task-title').val(task.title);
                    $('#task-description').val(task.description);
                    $('#task-status').val(task.status);
                    $('#task-priority').val(task.priority);
                    $('#task-assignee').val(task.assignee_id || '');
                    $('#task-due-date').val(task.due_date || '');
                    $('#task-estimated-hours').val(task.estimated_hours || '');
                    
                    // Cambiar título del modal
                    $('#modal-title').text('Editar Tarea');
                    
                    // Mostrar modal
                    $('.bugboard-modal').show();
                    
                    console.log('Modal llenado y mostrado');
                } else {
                    console.error('Error al cargar tarea:', response);
                    showNotification('Error al cargar la tarea: ' + (response.data ? response.data.message : 'Error desconocido'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX al cargar tarea:', error);
                showNotification('Error al cargar la tarea: ' + error, 'error');
            }
        });
    };
    
    window.deleteTask = function(taskId) {
        if (confirm('¿Estás seguro de que quieres eliminar esta tarea?')) {
            console.log('Eliminando tarea:', taskId);
            
            // Eliminación optimista - quitar inmediatamente de la UI
            var taskElement = $('.bugboard-task[data-task-id="' + taskId + '"]');
            taskElement.addClass('task-deleting');
            
            $.ajax({
                url: bugboardAjaxUrl,
                type: 'POST',
                data: {
                    action: 'bugboard_delete_task',
                    nonce: bugboardNonce,
                    task_id: taskId
                },
                success: function(response) {
                    if (response.success) {
                        taskElement.fadeOut(300, function() {
                            $(this).remove();
                            updateColumnCounts();
                        });
                        showNotification('Tarea eliminada correctamente', 'success');
                    } else {
                        taskElement.removeClass('task-deleting');
                        showNotification('Error al eliminar la tarea', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al eliminar tarea:', error);
                    taskElement.removeClass('task-deleting');
                    showNotification('Error al eliminar la tarea', 'error');
                }
            });
        }
    };
    
    window.openTaskModal = function(status) {
        openTaskModal(status);
    };
    
    window.closeTaskModal = function() {
        closeTaskModal();
    };
}); 