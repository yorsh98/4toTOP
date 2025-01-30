
    $(document).ready(function () {
        $('#miTabla').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('oficios.data') }}",
            columns: [
                { data: 'Numentregado', name: 'Numentregado' },
                { data: 'año', name: 'año' },
                { data: 'CausaAsig', name: 'CausaAsig' },
                { data: 'UserSolicitante', name: 'UserSolicitante' },
                { data: 'UserDirigido', name: 'UserDirigido' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ]
        });
    });
