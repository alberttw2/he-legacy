# Bugfixes & Features Pending

Issues reportados del juego original que necesitan verificación/arreglo.

## Batch 1 (del changelog original)

| # | Feature | Estado | Notas |
|---|---------|--------|-------|
| 1 | Certifications can be taken multiple times | BROKEN | `cert_add` impide retomar certs completadas |
| 2 | IP change asks for confirmation | MISSING | RESET_IP completa sin confirmación |
| 3 | Alert when DDoS targets you | MISSING | No hay notificación a la víctima |
| 4 | Changing IP removes from FBI/Safenet (15min) | PARTIAL | Borra de lists pero sin timer ni FBI/Safenet |
| 5 | NPC/VPC tag in hacked database | MISSING | No hay tag visual NPC vs VPC |
| 6 | Hard drive format | BROKEN | case 10 está vacío, no borra nada |
| 7 | Unicity of DDoS and ChangeIP process | WORKING | Ya previene duplicados |
| 8 | Folder size display | MISSING | Folders no muestran tamaño total |
| 9 | Bounty rebalanced for money transfer | WORKING | $1000-1500 para type 4 |
| 10 | RAM and file size reduced | WORKING | Valores razonables en SoftwareGenerator |
| 11 | Server prices decreased for 6+ servers | BROKEN | pow(10, N+2) = $100M para 6to server |
| 12 | Hiding software newbie protection | MISSING | Sin check de nivel |

## Batch 2

| # | Feature | Estado | Notas |
|---|---------|--------|-------|
| 13 | Repair External HD upload | TODO | Verificar upload a XHD |
| 14 | Repair Missions | TODO | Verificar flujo completo de misiones |
| 15 | IP tracing exploit patched | TODO | Verificar si hay exploit de IP tracing |
| 16 | Balance adjustments for low level software | TODO | Ajustar stats de software nivel bajo |
| 17 | Clan logs (log the logs) | TODO | Verificar que clanes tienen logs |
| 18 | FBI bounty increase mail errors on DDoS | TODO | Verificar mail de bounty FBI |
| 19 | Can claim same bounty multiple times | TODO | Verificar duplicación de bounties |
