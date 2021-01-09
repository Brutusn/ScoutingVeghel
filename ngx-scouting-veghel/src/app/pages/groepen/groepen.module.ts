import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GroupsPageComponent } from './groups-page/groups-page.component';
import { RouterModule, Routes } from '@angular/router';
import { SvCardModule } from '../../shared/sv-card/sv-card.module';
import { MatIconModule } from '@angular/material/icon';

const routes: Routes = [
  {
    path: '',
    component: GroupsPageComponent,
  },
];

@NgModule({
  declarations: [GroupsPageComponent],
  imports: [
    CommonModule,
    RouterModule.forChild(routes),
    SvCardModule,
    MatIconModule
  ]
})
export class GroepenModule { }
