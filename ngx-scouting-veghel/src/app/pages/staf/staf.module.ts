import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { StaffPageComponent } from './staff-page/staff-page.component';
import { Routes, RouterModule } from '@angular/router';

const routes: Routes = [
  {
    path: '',
    component: StaffPageComponent,
  },
];

@NgModule({
  declarations: [StaffPageComponent],
  imports: [
    CommonModule,
    RouterModule.forChild(routes)
  ]
})
export class StafModule { }
