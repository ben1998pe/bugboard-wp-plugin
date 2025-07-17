/**
 * JavaScript para el tablero Kanban de BugBoard
 */

jQuery(document).ready(function($) {
    
    // Variables globales
    var currentTask = null;
    var isDragging = false;
    
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
            if (e.target.classList.contains('task-actions')) return;
            
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
        if (!isDragging || !currentTask) return;
        
        var targetColumn = $(e.target).closest('.bugboard-tasks');
        if (targetColumn.length > 0) {
            var newStatus = targetColumn.closest('.bugboard-column').data('status');
            var taskId = currentTask.data('task-id');
            
            updateTaskStatus(taskId, newStatus);
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
        var assigneeName = task.assignee ? getUserName(task.assignee) : 'Sin asignar';
        
        var taskHtml = `
            <div class="bugboard-task ${priorityClass}" data-task-id="${task.id}" data-status="${task.status}">
                <div class="task-header">
                    <h4 class="task-title">${task.title}</h4>
                    <div class="task-actions">
                        <button class="edit-task-btn" onclick="editTask(${task.id})">✏️</button>
                        <button class="delete-task-btn" onclick="deleteTask(${task.id})">🗑️</button>
                    </div>
                </div>
                <div class="task-content">
                    <p class="task-description">${task.description || 'Sin descripción'}</p>
                </div>
                <div class="task-footer">
                    <span class="task-priority ${priorityClass}">${task.priority}</span>
                    <span class="task-assignee">👤 ${assigneeName}</span>
                    <span class="task-date">📅 ${task.date}</span>
                </div>
            </div>
        `;
        
        return $(taskHtml);
    }
    
    /**
     * Obtener nombre de usuario
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
        $('#modal-title').text('Añadir Nueva Tarea');
        
        $('#task-modal').show();
    }
    
    /**
     * Cerrar modal
     */
    function closeTaskModal() {
        $('#task-modal').hide();
    }
    
    /**
     * Guardar tarea
     */
    function saveTask() {
        var formData = {
            task_id: $('#task-id').val(),
            task_title: $('#task-title').val(),
            task_description: $('#task-description').val(),
            task_status: $('#task-status').val(),
            task_priority: $('#task-priority').val(),
            task_assignee: $('#task-assignee').val()
        };
        
        console.log('Guardando tarea:', formData);
        
        $.ajax({
            url: bugboardAjaxUrl,
            type: 'POST',
            data: {
                action: 'bugboard_save_task',
                nonce: bugboardNonce,
                ...formData
            },
            success: function(response) {
                console.log('Respuesta de guardar:', response);
                if (response.success) {
                    closeTaskModal();
                    loadTasks();
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
     * Actualizar estado de tarea
     */
    function updateTaskStatus(taskId, newStatus) {
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
                if (response.success) {
                    loadTasks();
                    showNotification('Estado actualizado correctamente', 'success');
                } else {
                    showNotification('Error al actualizar el estado', 'error');
                }
            },
            error: function() {
                showNotification('Error al actualizar el estado', 'error');
            }
        });
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
        console.log('URL:', bugboardAjaxUrl);
        console.log('Nonce:', bugboardNonce);
        
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
                    $('#task-assignee').val(task.assignee);
                    
                    // Cambiar título del modal
                    $('#modal-title').text('Editar Tarea');
                    
                    // Mostrar modal
                    $('#task-modal').show();
                    
                    console.log('Modal llenado y mostrado');
                } else {
                    console.error('Error al cargar tarea:', response);
                    showNotification('Error al cargar la tarea: ' + (response.data ? response.data.message : 'Error desconocido'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX al cargar tarea:', error);
                console.error('Status:', status);
                console.error('XHR:', xhr);
                console.error('Response Text:', xhr.responseText);
                showNotification('Error al cargar la tarea: ' + error, 'error');
            }
        });
    };
    
    window.deleteTask = function(taskId) {
        if (confirm('¿Estás seguro de que quieres eliminar esta tarea?')) {
            console.log('Eliminando tarea:', taskId);
            
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
                        showNotification('Tarea eliminada correctamente', 'success');
                        loadTasks(); // Recargar tareas
                    } else {
                        showNotification('Error al eliminar la tarea', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al eliminar tarea:', error);
                    showNotification('Error al eliminar la tarea', 'error');
                }
            });
        }
    };
    
    window.closeTaskModal = function() {
        closeTaskModal();
    };
}); 