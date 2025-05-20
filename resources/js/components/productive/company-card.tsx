import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "../ui/card";
import { Company } from "@/types/productive";

interface CompanyCardProps {
  company: Company;
}

export function CompanyCard({ company }: CompanyCardProps) {
  return (
    <Card className="overflow-hidden">
      <CardHeader className="pb-4">
        <CardTitle className="text-xl font-bold truncate">{company.name}</CardTitle>
        <CardDescription>ID: {company.id}</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-2">
          <div className="text-sm text-muted-foreground">            Created: {new Date(company.createdAt).toLocaleDateString()}
          </div>
          <div className="text-sm text-muted-foreground">
            Updated: {new Date(company.updatedAt).toLocaleDateString()}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
