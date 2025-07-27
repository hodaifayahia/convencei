<script setup>
import { defineProps, ref, onMounted, computed } from 'vue';

const props = defineProps({
    ficheNavette: {
        type: Object,
        required: true,
    },
    appBaseUrl: {
        type: String,
        required: true,
    },
});

// Cache the base64 image
const cachedBase64Image = ref(null);
const imageLoadingError = ref(false);

// Create a single formatter instance for better performance
const currencyFormatter = new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
});

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '0,00 DZD';
    return currencyFormatter.format(amount);
};

// Preload and cache image on component mount
onMounted(async () => {
    await preloadHeaderImage();
});

const preloadHeaderImage = async () => {
    const possibleImageUrls = [
        `${window.location.origin}/storage/ENTETE.png`,
        `${props.appBaseUrl}/storage/ENTETE.png`,
        '/storage/ENTETE.png'
    ];

    for (const url of possibleImageUrls) {
        try {
            const base64 = await getBase64ImageOptimized(url);
            if (base64) {
                cachedBase64Image.value = base64;
                imageLoadingError.value = false;
                return;
            }
        } catch (error) {
            console.warn(`Failed to load image from ${url}`);
        }
    }
    imageLoadingError.value = true;
};

const getBase64ImageOptimized = async (url) => {
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3000);

        const response = await fetch(url, { 
            signal: controller.signal,
            cache: 'force-cache'
        });
        clearTimeout(timeoutId);

        if (!response.ok) return null;
        
        const blob = await response.blob();
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    } catch (error) {
        return null;
    }
};

// Pre-compute values to avoid repeated calculations including doctors
const ticketData = computed(() => ({
    patientFirstName: props.ficheNavette.patient?.Firstname || 'N/A',
    patientLastName: props.ficheNavette.patient?.Lastname || 'N/A',
    patientPhone: props.ficheNavette.patient?.phone || 'N/A',
    assuredFirstName: props.ficheNavette.insured?.Firstname || 'N/A',
    assuredLastName: props.ficheNavette.insured?.Lastname || 'N/A',
    assuredPhone: props.ficheNavette.insured?.phone || 'N/A',
    fnNumber: props.ficheNavette.FNnumber || 'N/A',
    ficheDate: props.ficheNavette.fiche_date || 'N/A',
    // Add doctors information
    doctorsNames: props.ficheNavette.doctors && props.ficheNavette.doctors.length > 0 
        ? props.ficheNavette.doctors.map(doctor => {
            const name = doctor.user?.name || 'Médecin Inconnu';
            const specialization = doctor.specialization?.name || '';
            return specialization ? `${name}` : name;
          }).join(', ')
        : 'Aucun médecin assigné',
    currentDateTime: new Date().toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}));

const prestationsHtml = computed(() => {
    if (!props.ficheNavette.items?.length) {
        return `<div class="ticket-row"><span class="ticket-label">Prestations :</span> Aucune prestation associée.</div>`;
    }

    const rows = props.ficheNavette.items.map(item => {
        const designation = item.convention?.designation_prestation || '-';
        const chargeEnt = formatCurrency(item.convention?.montant_prise_charge_beneficiaire || 0);
        return `<tr><td>${designation}</td><td>${chargeEnt}</td></tr>`;
    }).join('');

    return `
        <div class="ticket-row">
            <span class="ticket-label">Détails des Prestations :</span>
        </div>
        <div class="prestations-list">
            <table>
                <thead>
                    <tr><th>Désignation</th><th>Charge BEN.</th></tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
});

// Optimized CSS with minimal fonts
const optimizedCSS = `
    @page { 
        size: 80mm auto; 
        margin: 2mm; 
    }
    body {
        font-family: Arial, sans-serif;
        font-size: 9pt;
        line-height: 1.2;
        margin: 0;
        padding: 0;
        width: 76mm;
        color: #000;
        background: #fff;
    }
    .container { 
        width: 100%; 
        margin: 0; 
        padding: 0; 
        box-sizing: border-box; 
    }
    .header-image { 
        width: 100%; 
        max-height: 15mm; 
        margin-bottom: 2mm; 
        display: block; 
        object-fit: contain; 
    }
    .header-placeholder { 
        width: 100%; 
        height: 15mm; 
        text-align: center; 
        font-weight: bold; 
        font-size: 10pt; 
        padding: 2mm 0; 
        border: 1px solid #000; 
        margin-bottom: 2mm; 
        color: #000; 
        background: #f0f0f0; 
        box-sizing: border-box; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
    }
    .ticket-content {
        margin: 0;
        padding: 0;
    }
    .ticket-row { 
        margin: 1mm 0; 
        padding: 0.5mm 0; 
        border-bottom: 1px dotted #666; 
        word-wrap: break-word; 
        display: flex; 
        justify-content: space-between; 
    }
    .ticket-label { 
        font-weight: bold; 
        color: #000; 
        flex-shrink: 0; 
        margin-right: 2mm; 
    }
    .ticket-value { 
        text-align: right; 
        flex-grow: 1; 
    }
    .prestations-list { 
        margin: 1mm 0; 
        font-size: 8pt; 
    }
    .prestations-list table { 
        width: 100%; 
        border-collapse: collapse; 
        margin: 1mm 0; 
    }
    .prestations-list th, .prestations-list td { 
        border: 1px solid #666; 
        padding: 1mm; 
        text-align: left; 
        font-size: 8pt; 
    }
    .prestations-list th { 
        background-color: #e0e0e0; 
        font-weight: bold; 
        color: #000; 
    }
    .prestations-list td { 
        color: #000; 
    }
    .ticket-footer { 
        margin-top: 2mm; 
        font-size: 7pt; 
        border-top: 1px dashed #000; 
        padding-top: 1mm; 
        text-align: center; 
        color: #000; 
    }
    .footer-row { 
        margin: 0.5mm 0; 
    }
    @media print {
        @page { 
            size: 80mm auto; 
            margin: 2mm; 
        }
        body { 
            width: 76mm; 
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact; 
        }
        .container { 
            page-break-inside: avoid; 
        }
    }
`;

// Streamlined print function with doctors and date repositioned
const printTicket = async () => {
    const data = ticketData.value;
    
    // Use cached image or placeholder
    const headerImageHtml = cachedBase64Image.value
        ? `<img src="${cachedBase64Image.value}" class="header-image" alt="En-tête">`
        : `<div class="header-placeholder">ENTETE</div>`;

    const ticketContent = `<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket FN-${data.fnNumber}</title>
    <style>${optimizedCSS}</style>
</head>
<body>
    <div class="container">
        <div style="text-align: center;">${headerImageHtml}</div>
        <div class="ticket-content">
            <div class="ticket-row">
                <span class="ticket-label">Date :</span>
                <span class="ticket-value">${data.ficheDate}</span>
            </div>
            <div class="ticket-row">
                <span class="ticket-label">FN Numéro :</span>
                <span class="ticket-value">${data.fnNumber}</span>
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Patient :</span>
                <span class="ticket-value">${data.patientFirstName.toUpperCase()} ${data.patientLastName.toUpperCase()}</span>
            </div>

            <div class="ticket-row">
                <span class="ticket-label">Assuré :</span>
                <span class="ticket-value">${data.assuredLastName.toUpperCase()} ${data.assuredFirstName.toUpperCase()}</span>
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Téléphone Assuré :</span>
                <span class="ticket-value">${data.assuredPhone}</span>
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Médecin(s) :</span>
                <span class="ticket-value">${data.doctorsNames}</span>
            </div>
            ${prestationsHtml.value}
            <div class="ticket-row">
                <span class="ticket-label">Total Charge Bén. (Fiche) :</span>
                <span class="ticket-value">${formatCurrency(props.ficheNavette.patient_share)}</span>
            </div>
        </div>
        <div class="ticket-footer">
            <div class="footer-row">Imprimé le : ${data.currentDateTime}</div>
        </div>
    </div>
</body>
</html>`;

    try {
        const iframe = document.createElement('iframe');
        Object.assign(iframe.style, {
            position: 'absolute',
            left: '-9999px',
            top: '-9999px',
            width: '1px',
            height: '1px',
            visibility: 'hidden'
        });

        document.body.appendChild(iframe);

        const doc = iframe.contentWindow.document;
        doc.open();
        doc.write(ticketContent);
        doc.close();

        // Use promise-based approach for better performance
        await new Promise((resolve) => {
            const checkReady = () => {
                if (doc.readyState === 'complete') {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                    resolve();
                } else {
                    requestAnimationFrame(checkReady);
                }
            };
            requestAnimationFrame(checkReady);
        });

        // Clean up immediately after print dialog opens
        setTimeout(() => {
            if (iframe?.parentNode) {
                document.body.removeChild(iframe);
            }
        }, 100);

    } catch (error) {
        console.error('Error printing thermal ticket:', error);
        alert('Une erreur est survenue lors de l\'impression du ticket thermique. Veuillez réessayer.');
    }
};
</script>

<template>
    <button @click="printTicket"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm-5-8h14v-2H4v2z"></path>
        </svg>
        Imprimer le Ticket
    </button>
</template>
