import { Component } from '@angular/core';
import { SvMenuConfiguaration } from '@shared/sv-header/menu.interface';

@Component({
  selector: 'sv-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css'],
})
export class AppComponent {
  readonly menuConfiguaration: SvMenuConfiguaration = [
    {
      label: 'Groepen',
      routerLink: '/groepen',
    },
    {
      label: 'Activiteiten',
      routerLink: '/activiteiten',
    },
    {
      label: 'Ouders',
      routerLink: '/ouders',
    },
    {
      label: 'Staf',
      routerLink: '/staf',
    },
    {
      label: 'Contact',
      routerLink: '/contact',
    },
    {
      label: 'Verhuur',
      routerLink: '/verhuur',
    },
  ];
}
