import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GroupsPageComponent } from './groups-page/groups-page.component';
import { RouterModule, Routes } from '@angular/router';

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
    RouterModule.forChild(routes)
  ]
})
export class GroepenModule { }
