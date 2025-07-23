<script setup>
import { defineProps } from 'vue';

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

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '0.00 DZD';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'DZD'
    }).format(amount);
};

// Function to convert image to base64
const getBase64Image = async (url) => {
    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Failed to fetch image: ${response.statusText}`);
        }
        const blob = await response.blob();
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    } catch (error) {
        console.error('Error loading image:', error);
        return null;
    }
};

const printTicket = async () => {
    const patientFirstName = props.ficheNavette.patient?.Firstname || 'N/A';
    const patientLastName = props.ficheNavette.patient?.Lastname || 'N/A';
    const patientPhone = props.ficheNavette.patient?.phone || 'N/A';
    const assuredFirstName = props.ficheNavette.first_name_beneficiary || 'N/A';
    const assuredLastName = props.ficheNavette.last_name_beneficiary || 'N/A';
    const assuredPhone = props.ficheNavette.phone_beneficiary || 'N/A';
    const fnNumber = props.ficheNavette.FNnumber || 'N/A';
    const ficheDate = props.ficheNavette.fiche_date || 'N/A';
    const currentDateTime = new Date().toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    const userName = 'Current User';

    // Try to load image as base64 with multiple fallback URLs
    const possibleImageUrls = [
        `${window.location.origin}/storage/ENTETE.png`,
        `${props.appBaseUrl}/storage/ENTETE.png`,
        '/storage/ENTETE.png'
    ];

    let base64Image = null;
    for (const url of possibleImageUrls) {
        base64Image = await getBase64Image(url);
        if (base64Image) break;
    }

    // Create header image HTML
    const headerImageHtml = base64Image 
        ? `<img src="${base64Image}" class="header-image" alt="En-tête">`
        : `<div class="header-placeholder">ENTETE</div>`;

    let prestationsHtml = '';
    if (props.ficheNavette.items && props.ficheNavette.items.length > 0) {
        prestationsHtml = `
            <div class="ticket-row">
                <span class="ticket-label">Détails des Prestations :</span>
            </div>
            <div class="prestations-list">
                <table>
                    <thead>
                        <tr>
                            <th>Désignation</th>
                            <th>Charge Ent.</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        props.ficheNavette.items.forEach(item => {
            const designation = item.convention?.designation_prestation || '-';
            const chargeEnt = formatCurrency(item.convention?.montant_prise_charge_entreprise);
            prestationsHtml += `
                            <tr>
                                <td>${designation}</td>
                                <td>${chargeEnt}</td>
                            </tr>
            `;
        });
        prestationsHtml += `
                    </tbody>
                </table>
            </div>
        `;
    } else {
        prestationsHtml = `
            <div class="ticket-row">
                <span class="ticket-label">Prestations :</span> Aucune prestation associée.
            </div>
        `;
    }

    const ticketContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                @page {
                    size: 80mm auto;
                    margin: 2mm;
                }
                body {
                    font-family: 'XB Zar', 'DejaVu Sans', monospace;
                    font-size: 9pt;
                    line-height: 1.2;
                    margin: 0;
                    padding: 0;
                    width: 76mm; /* 80mm - 4mm margins */
                    color: #000;
                    background: #fff;
                    min-height: auto;
                }
                .container {
                    width: 100%;
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                    min-height: auto;
                }
                .header-image {
                    width: 100%;
                    max-height: 15mm;
                    margin-bottom: 2mm;
                    display: block;
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
                }
                .ticket-label {
                    font-weight: bold;
                    color: #000;
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
            </style>
        </head>
        <body>
            <div class="container">
                <div style="text-align: center;">
                    ${headerImageHtml}
                </div>

                <div class="ticket-content">
                    <div class="ticket-row">
                        <span class="ticket-label">Patient :</span>
                        ${patientFirstName.toUpperCase()} ${patientLastName.toUpperCase()}
                    </div>
                    <div class="ticket-row">
                        <span class="ticket-label">Téléphone Patient :</span>
                        ${patientPhone}
                    </div>
                    <div class="ticket-row">
                        <span class="ticket-label">Assuré :</span>
                        ${assuredLastName.toUpperCase()} ${assuredFirstName.toUpperCase()}
                    </div>
                    <div class="ticket-row">
                        <span class="ticket-label">Téléphone Assuré :</span>
                        ${assuredPhone}
                    </div>
                    <div class="ticket-row">
                        <span class="ticket-label">FN Numéro :</span>
                        ${fnNumber}
                    </div>
                    <div class="ticket-row">
                        <span class="ticket-label">Date :</span>
                        ${ficheDate}
                    </div>
                    <div class="ticket-row">
                        <span class="ticket-label">Phone :</span>
                        029 23 99 99 / 05 55 88 99 97
                    </div>
                    ${prestationsHtml}
                    <div class="ticket-row">
                        <span class="ticket-label">Total Charge Bén. (Fiche) :</span>
                        ${formatCurrency(props.ficheNavette.organisme_share)}
                    </div>
                </div>

                <div class="ticket-footer">
                    <div class="footer-row">
                        Imprimé le : ${currentDateTime}
                    </div>
                </div>
            </div>
        </body>
        </html>
    `;

    try {
        // Create a hidden iframe with dynamic height
        const iframe = document.createElement('iframe');
        iframe.style.position = 'absolute';
        iframe.style.top = '-10000px';
        iframe.style.left = '-10000px';
        iframe.style.width = '80mm';
        iframe.style.height = 'auto';
        iframe.style.minHeight = '50mm';
        iframe.style.border = 'none';
        
        document.body.appendChild(iframe);
        
        const doc = iframe.contentWindow.document;
        doc.open();
        doc.write(ticketContent);
        doc.close();

        // Wait for content to load then trigger print
        iframe.onload = () => {
            setTimeout(() => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                
                // Clean up - remove iframe after printing
                setTimeout(() => {
                    if (iframe && iframe.parentNode) {
                        document.body.removeChild(iframe);
                    }
                }, 1500);
            }, 800);
        };

    } catch (error) {
        console.error('Error printing thermal ticket:', error);
        alert('Erreur lors de l\'impression du ticket thermique.');
    }
};
</script>

<template>
    <button @click="printTicket"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm-5-8h14v-2H4v2z"></path>
        </svg>
        Print Ticket
    </button>
</template>
