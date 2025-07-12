$(document).ready(function() {
    // ==========================================
    // GESTIONE MODALI E INTERAZIONI
    // ==========================================
    
    // Gestione click bottone elimina
    $('.btn-elimina').on('click', function() {
        const indice = $(this).data('indice');
        const titolo = $(this).data('titolo');
        
        $('#indiceElimina').val(indice);
        $('#titoloElimina').text(titolo);
        $('#modalElimina').modal('show');
    });
    
    // Gestione click bottone modifica
    $('.btn-modifica').on('click', function() {
        const indice = $(this).data('indice');
        const titolo = $(this).data('titolo');
        const voto = $(this).data('voto');
        const data = $(this).data('data');
        const console = $(this).data('console');
        
        // Popola il form di modifica
        $('#indiceModifica').val(indice);
        $('#titoloModifica').val(titolo);
        $('#votoModifica').val(voto);
        $('#dataModifica').val(data);
        $('#consoleModifica').val(console);
        
        $('#modalModifica').modal('show');
    });
    
    // ==========================================
    // VALIDAZIONE FORM IN TEMPO REALE
    // ==========================================
    
    // Validazione titolo (no caratteri speciali pericolosi)
    $('#titolo, #titoloModifica').on('input', function() {
        const value = $(this).val();
        const isValid = value.length >= 2 && value.length <= 100;
        
        if (isValid) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
        }
    });
    
    // Validazione voto (range 1-10)
    $('#voto, #votoModifica').on('input', function() {
        const value = parseFloat($(this).val());
        const isValid = value >= 1 && value <= 10;
        
        if (isValid) {
            $(this).removeClass('is-invalid').addClass('is-valid');
            // Aggiorna colore badge dinamicamente
            updateVotoBadge($(this), value);
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
        }
    });
    
    // Validazione data (non futura)
    $('#data_rilascio, #dataModifica').on('change', function() {
        const selectedDate = new Date($(this).val());
        const today = new Date();
        const isValid = selectedDate <= today;
        
        if (isValid) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
            showTooltip($(this), 'La data non può essere futura!');
        }
    });
    
    // ==========================================
    // FUNZIONALITÀ AVANZATE
    // ==========================================
    
    // Ricerca dinamica nella tabella
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.gioco-row').each(function() {
            const gameTitle = $(this).find('td:first-child').text().toLowerCase();
            const gameConsole = $(this).find('.badge.bg-secondary').text().toLowerCase();
            
            if (gameTitle.includes(searchTerm) || gameConsole.includes(searchTerm)) {
                $(this).show().addClass('highlight-search');
            } else {
                $(this).hide().removeClass('highlight-search');
            }
        });
        
        // Mostra messaggio se nessun risultato
        const visibleRows = $('.gioco-row:visible').length;
        if (visibleRows === 0 && searchTerm !== '') {
            showNoResultsMessage();
        } else {
            hideNoResultsMessage();
        }
    });
    
    // Ordinamento tabella
    $('.sortable-header').on('click', function() {
        const column = $(this).data('column');
        const currentOrder = $(this).data('order') || 'asc';
        const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
        
        $(this).data('order', newOrder);
        sortTable(column, newOrder);
        updateSortIcons($(this), newOrder);
    });
    
    // Auto-save form data in caso di errori
    $('#formAggiungi input, #formAggiungi select').on('change', function() {
        const formData = $('#formAggiungi').serialize();
        sessionStorage.setItem('formBackup', formData);
    });
    
    // Ripristina form data se presente
    const formBackup = sessionStorage.getItem('formBackup');
    if (formBackup && $('.alert-success').length === 0) {
        restoreFormData(formBackup);
    } else if ($('.alert-success').length > 0) {
        sessionStorage.removeItem('formBackup');
    }
    
    // ==========================================
    // ANIMAZIONI E EFFETTI VISIVI
    // ==========================================
    
    // Animazione aggiunta gioco
    $('#formAggiungi').on('submit', function() {
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Aggiungendo...');
        submitBtn.prop('disabled', true);
    });
    
    // Highlight riga dopo modifica
    $('.gioco-row').each(function(index) {
        $(this).delay(index * 100).fadeIn(500);
    });
    
    // Tooltip personalizzati
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Conferma eliminazione con doppio click
    let deleteClickCount = 0;
    $('.btn-elimina').on('click', function(e) {
        deleteClickCount++;
        if (deleteClickCount === 1) {
            setTimeout(() => deleteClickCount = 0, 2000);
        }
    });
    
    // ==========================================
    // FUNZIONI HELPER
    // ==========================================
    
    function updateVotoBadge(element, voto) {
        const preview = element.closest('.card-body').find('.voto-preview');
        if (preview.length === 0) {
            element.after(`<small class="voto-preview text-muted mt-1 d-block"></small>`);
        }
        
        let badgeClass = 'danger';
        if (voto >= 8) badgeClass = 'success';
        else if (voto >= 6) badgeClass = 'warning';
        
        $(element).siblings('.voto-preview').html(
            `<span class="badge bg-${badgeClass}">${voto}/10</span>`
        );
    }
    
    function showTooltip(element, message) {
        element.attr('title', message).tooltip('show');
        setTimeout(() => element.tooltip('hide'), 3000);
    }
    
    function sortTable(column, order) {
        const tbody = $('.table tbody');
        const rows = tbody.find('tr').toArray();
        
        rows.sort((a, b) => {
            let aVal, bVal;
            
            switch(column) {
                case 'titolo':
                    aVal = $(a).find('td:nth-child(1)').text();
                    bVal = $(b).find('td:nth-child(1)').text();
                    break;
                case 'voto':
                    aVal = parseFloat($(a).find('.badge').text().split('/')[0]);
                    bVal = parseFloat($(b).find('.badge').text().split('/')[0]);
                    break;
                case 'data':
                    aVal = new Date($(a).find('td:nth-child(3)').text().split('/').reverse().join('-'));
                    bVal = new Date($(b).find('td:nth-child(3)').text().split('/').reverse().join('-'));
                    break;
                case 'console':
                    aVal = $(a).find('td:nth-child(4) .badge').text();
                    bVal = $(b).find('td:nth-child(4) .badge').text();
                    break;
            }
            
            if (order === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });
        
        tbody.empty().append(rows);
    }
    
    function updateSortIcons(header, order) {
        $('.sortable-header i').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');
        const icon = order === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
        header.find('i').removeClass('fa-sort').addClass(icon);
    }
    
    function showNoResultsMessage() {
        if ($('.no-results-message').length === 0) {
            $('.table tbody').after(`
                <div class="no-results-message text-center py-4">
                    <i class="fas fa-search fa-2x text-muted mb-2"></i>
                    <p class="text-muted">Nessun gioco trovato per la ricerca corrente</p>
                </div>
            `);
        }
    }
    
    function hideNoResultsMessage() {
        $('.no-results-message').remove();
    }
    
    function restoreFormData(formData) {
        const params = new URLSearchParams(formData);
        params.forEach((value, key) => {
            $(`#${key}`).val(value);
        });
        
        // Mostra notifica di ripristino
        $('body').prepend(`
            <div class="alert alert-info alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999;" role="alert">
                <i class="fas fa-info-circle me-2"></i>Dati del form ripristinati
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        setTimeout(() => $('.alert-info').fadeOut(), 3000);
    }
    
    // ==========================================
    // STATISTICHE DINAMICHE
    // ==========================================
    
    function updateStatistics() {
        const totalGames = $('.gioco-row').length;
        const avgRating = calculateAverageRating();
        const consoleCounts = getConsoleDistribution();
        
        // Aggiorna badge contatore
        $('.badge.bg-light.text-dark').text(`${totalGames} giochi`);
        
        // Aggiungi statistiche dettagliate se non esistono
        if ($('.stats-container').length === 0) {
            $('.card-header').after(`
                <div class="stats-container bg-light p-3 border-bottom">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <small class="text-muted">Media Voti</small>
                            <div class="fw-bold avg-rating">${avgRating}</div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Console Più Usata</small>
                            <div class="fw-bold top-console">${getTopConsole(consoleCounts)}</div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Ultimo Aggiunto</small>
                            <div class="fw-bold last-added">${getLastAdded()}</div>
                        </div>
                    </div>
                </div>
            `);
        }
    }
    
    function calculateAverageRating() {
        let total = 0;
        let count = 0;
        
        $('.badge:contains("/10")').each(function() {
            const rating = parseFloat($(this).text().split('/')[0]);
            total += rating;
            count++;
        });
        
        return count > 0 ? (total / count).toFixed(1) : '0.0';
    }
    
    function getConsoleDistribution() {
        const consoles = {};
        $('.badge.bg-secondary').each(function() {
            const console = $(this).text();
            consoles[console] = (consoles[console] || 0) + 1;
        });
        return consoles;
    }
    
    function getTopConsole(consoleCounts) {
        let topConsole = 'N/A';
        let maxCount = 0;
        
        for (const [console, count] of Object.entries(consoleCounts)) {
            if (count > maxCount) {
                maxCount = count;
                topConsole = console;
            }
        }
        
        return topConsole;
    }
    
    function getLastAdded() {
        const rows = $('.gioco-row');
        return rows.length > 0 ? $(rows[rows.length - 1]).find('td:first-child strong').text() : 'N/A';
    }
    
    // Aggiorna statistiche al caricamento
    updateStatistics();
    
    // ==========================================
    // ESPORTAZIONE DATI
    // ==========================================
    
    // Aggiungi bottone esportazione se non presente
    if ($('.export-btn').length === 0) {
        $('.card-header h4').after(`
            <div class="btn-group ms-2" role="group">
                <button type="button" class="btn btn-sm btn-outline-light export-btn" 
                        data-format="json" title="Esporta JSON">
                    <i class="fas fa-download"></i> JSON
                </button>
                <button type="button" class="btn btn-sm btn-outline-light export-btn" 
                        data-format="csv" title="Esporta CSV">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
            </div>
        `);
    }
    
    $('.export-btn').on('click', function() {
        const format = $(this).data('format');
        exportData(format);
    });
    
    function exportData(format) {
        const games = [];
        
        $('.gioco-row').each(function() {
            const row = $(this);
            games.push({
                titolo: row.find('td:nth-child(1) strong').text(),
                voto: parseFloat(row.find('.badge:contains("/10")').text().split('/')[0]),
                data_rilascio: row.find('td:nth-child(3)').text(),
                console: row.find('td:nth-child(4) .badge').text()
            });
        });
        
        let content, filename, mimeType;
        
        if (format === 'json') {
            content = JSON.stringify(games, null, 2);
            filename = 'libreria_giochi.json';
            mimeType = 'application/json';
        } else if (format === 'csv') {
            const headers = ['Titolo', 'Voto', 'Data Rilascio', 'Console'];
            const csvRows = [headers.join(',')];
            
            games.forEach(game => {
                csvRows.push([
                    `"${game.titolo}"`,
                    game.voto,
                    `"${game.data_rilascio}"`,
                    `"${game.console}"`
                ].join(','));
            });
            
            content = csvRows.join('\n');
            filename = 'libreria_giochi.csv';
            mimeType = 'text/csv';
        }
        
        // Crea e scarica il file
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        // Mostra conferma
        showSuccessToast(`File ${filename} scaricato con successo!`);
    }
    
    function showSuccessToast(message) {
        $('body').append(`
            <div class="toast-container position-fixed bottom-0 end-0 p-3">
                <div class="toast show" role="alert">
                    <div class="toast-header bg-success text-white">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong class="me-auto">Successo</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">${message}</div>
                </div>
            </div>
        `);
        
        setTimeout(() => $('.toast-container').remove(), 4000);
    }
});

// ==========================================
// FUNZIONI GLOBALI E UTILITY
// ==========================================

// Funzione per aggiornare l'interfaccia dopo operazioni CRUD
function refreshInterface() {
    location.reload();
}

// Gestione errori AJAX globale
$(document).ajaxError(function(event, xhr, settings, thrownError) {
    console.error('Errore AJAX:', thrownError);
    
    $('body').prepend(`
        <div class="alert alert-danger alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999;" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>Errore di connessione
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
});

// Prevenzione invio form multipli
$('form').on('submit', function() {
    $(this).find('button[type="submit"]').prop('disabled', true);
});

// Gestione responsive per mobile
function handleMobileView() {
    if ($(window).width() < 768) {
        $('.table-responsive').addClass('mobile-optimized');
        $('.btn-group').addClass('btn-group-vertical');
    }
}

function downloadXML() {
    const xmlContent = `<?xml version="1.0" encoding="UTF-8"?>\n${$('#modalExport textarea').val()}`;
    const blob = new Blob([xmlContent], { type: 'application/xml' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `games_export_${new Date().toISOString().split('T')[0]}.xml`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// Handle import form submission
$('#importForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = $(this).find('button[type="submit"]');
    
    submitBtn.prop('disabled', true);
    submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Importing...');
    
    $.ajax({
        url: window.location.href,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'html',
        success: function(data) {
            // Reload the page to show imported games
            location.reload();
        },
        error: function() {
            alert('Error during import. Please try again.');
            submitBtn.prop('disabled', false);
            submitBtn.html('<i class="fas fa-file-import me-2"></i>Import Games');
        }
    });
});

// Handle sorting
$('.sort-option').on('click', function(e) {
    e.preventDefault();
    const sortBy = $(this).data('sort');
    const direction = $(this).data('dir');
    
    $.ajax({
        url: window.location.href,
        type: 'GET',
        data: {
            action: 'sort',
            sortBy: sortBy,
            direction: direction
        },
        success: function(data) {
            // Update the games list
            $('.table tbody').html($(data).find('.table tbody').html());
        }
    });
});

// Handle grouping
$('.group-option').on('click', function(e) {
    e.preventDefault();
    const groupBy = $(this).data('group');
    
    if (groupBy === 'none') {
        // Show normal table
        $('.table').show();
        $('.grouped-view').remove();
        return;
    }
    
    $.ajax({
        url: window.location.href,
        type: 'GET',
        data: {
            action: 'group',
            groupBy: groupBy
        },
        success: function(data) {
            // Replace table with grouped view
            $('.table').hide();
            $('.grouped-view').remove();
            $('.card-body').append($(data).find('.grouped-view').html());
        }
    });
});

$(window).on('resize', handleMobileView);
handleMobileView();