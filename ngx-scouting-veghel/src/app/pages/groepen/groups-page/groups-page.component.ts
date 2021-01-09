import { Component, OnInit } from '@angular/core';
import { GroupCardConfiguration } from '../group-card-configuration';
import { GenderIcons } from '@interfaces';

@Component({
  selector: 'sv-groups-page',
  templateUrl: './groups-page.component.html',
  styleUrls: ['./groups-page.component.scss'],
})
export class GroupsPageComponent {
  readonly groupConfiguration: GroupCardConfiguration[] = [
    {
      title: 'Bevers',
      genderIcon: GenderIcons.Mixed,
      content:
        'De jongste leden van Scouting Veghel. De groep om kennis te maken met het scoutingspel op een afwisselende manier. De bosspelletjes worden afgewisseld met de mooiste knutselwerkjes! De bevers mogen soms zelfs een weekendje slapen op de blokhut.',
      age: '5-7 jr.',
      meetingTime: 'za. 9:15 - 11:00',
      img: 'assets/images/bevers.jpg',
    },
    {
      title: 'Welpen Klavertje 4',
      genderIcon: GenderIcons.Mixed,
      content:
        'De welpen groep waar zowel jongens als meisjes zich helemaal kunnen uitleven tijdens het programma. Luciferbanen, bruggen bouwen van papier, een 8 meter lang schilderij? Waarom niet, dat doen we toch even als we uit het bos komen? Appeltje, eitje.',
      age: '7-11 jr.',
      meetingTime: 'za. 11:15 - 13:15',
      img: 'assets/images/kl4.jpg',
    },
    {
      title: 'Welpen Sionie Horde',
      genderIcon: GenderIcons.Male,
      content:
        'De welpen van deze stoere jongensgroep zijn onbevreesd en veelzijdig! Geen bos is onbegaanbaar, geen spel is te moeilijk en ze laten geen uitdaging liggen! Ze spelen samen met anderen in de jungle, houden vol en zorgen goed voor de natuur.',
      age: '7-11 jr.',
      meetingTime: 'za. 9:00 - 11:00',
      img: 'assets/images/welpen.jpg',
    },
    {
      title: 'Welpen Nishaani Horde',
      genderIcon: GenderIcons.Mixed,
      content:
        'Vrijdag avond gaan zowel meisjes als jongens de uitdaging van de welpen aan. Zullen ze het donker in gaan of in het veilige licht van de blokhut blijven.',
      age: '7-11 jr.',
      meetingTime: 'vr. 18:30 - 20:30',
      img: 'assets/images/nishaani.jpg',
    },
    {
      title: 'Scouts de Dwaalsterren',
      genderIcon: GenderIcons.Mixed,
      content:
        'Een scout trekt er samen met anderen op uit om de wijde wereld te ontdekken en op iedere plek een goede grap te kunnen vertellen. De dames en heren van deze groep draaien hun hand niet om voor een kamp midden in het bos. Keuken zelf bouwen? Natuurlijk!',
      age: '11-15 jr.',
      meetingTime: 'di. 19:00 - 21:00',
      img: 'assets/images/scouts.jpg',
    },
    {
      title: 'Neil Armstrong Troep',
      genderIcon: GenderIcons.Male,
      content:
        'De verkenners van Scouting Veghel zijn stoere jongens die veel lol met elkaar hebben. Ze gaan het liefste lekker naar buiten: spelletjes in de bossen, leren survivallen, houthakken of een vuurtje maken. Kom jij ook meedoen met deze actieve en gezellige club?',
      age: '11-15 jr.',
      meetingTime: 'wo. 19:00 - 21:00',
      img: 'assets/images/verkenners.jpg',
    },
    {
      title: 'Explorers',
      genderIcon: GenderIcons.Mixed,
      content:
        `De laatste stap naar volwassenheid en onafhankelijkheid. Jij mag je eigen programma's maken, jij mag je eigen activiteiten organiseren. Van een feest tot een grote BBQ tot een mooi kampvuur. De begeleiding laat jou bijna volledig los! Kun jij de groep aan?!`,
      age: '15-18 jr.',
      meetingTime: 'ma. 19:30 - 21:30',
      img: 'assets/images/explo.jpg',
    },
    {
      title: 'S5 Stam',
      genderIcon: GenderIcons.Mixed,
      content:
        'Dit is de groep die het zelf voor het zeggen heeft! Er is geen leider die zal zeggen dat het niet mag. Van een avondje gamen tot de spelletjes die ze bij de welpen spelen, maar dan extreem! Het kan zo gek niet met deze groep S5-ers van de stam!',
      age: '18+.',
      meetingTime: 'zo. 20:00 - 23:00',
      img: 'assets/images/stam.jpg',
    },
  ];
}
